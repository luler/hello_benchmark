<?php

namespace app\controller\admin;

use app\ApiBaseController;
use app\common\exception\CommonException;
use app\common\helper\CasHelper;
use app\common\tool\JwtTool;
use app\model\User;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Console;
use think\facade\Db;

class LoginController extends ApiBaseController
{
    public function getAccessToken()
    {
        try {
            again:
            $fields = ['appid', 'appsecret'];
            $param = $this->request->only($fields);
            checkData($param, [
                'appid' => 'require',
                'appsecret' => 'require',
            ]);

            if ($param['appid'] == 'admin') {
                $accounts = [
                    'title' => config('app.base_account.appid'),
                    'appid' => config('app.base_account.appid'),
                    'appsecret' => User::translatePassword(config('app.base_account.appsecret')),
                    'is_admin' => 1,
                    'is_use' => 1,
                ];
                if (User::where('appid', 'admin')->count() == 0) {
                    User::create($accounts);
                }
            }


            $user = User::where('appid', $param['appid'])->find();
            if (empty($user)) {
                throw new CommonException('账号不存在');
            }
            if ($user['appsecret'] !== User::translatePassword($param['appsecret'])) {
                throw new CommonException('密码输入有误');
            }
            if ($user['is_use'] != 1) {
                throw new CommonException('账号已被禁用');
            }

            $jwt = new JwtTool();
            $res = $jwt->jsonReturnToken(['uid' => $user['id']]);
            $res['is_admin'] = $user['is_admin'];
            return $this->successResponse('登录成功', $res);
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'Unknown database') || stripos($e->getMessage(), 'Base table or view not found')) {
                $config = Config::get('database');
                $temp = $config['connections'][$config['default']];
                $database_name = $temp['database'];
                $temp['database'] = '';
                $config['connections']['temp'] = $temp;
                Config::set($config, 'database');
                Db::connect('temp')->execute('create database if not exists ' . $database_name . ' DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci');
                Console::call('migrate:run');
                goto again;
            }
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * 退出登录
     * @return \think\response\Redirect
     * @throws CommonException
     */
    public function logout()
    {
        $field = ['redirect', 'authorization'];
        $param = $this->_apiParam($field);
        checkData($param, [
            'authorization|授权token' => 'require',
            'redirect|回调地址' => 'require',
        ]);

        JwtTool::instance()->logout($param['authorization']);
        return redirect($param['redirect']);
    }

    /**
     * CAS登录
     * @return \think\response\Redirect
     * @throws \Exception
     */
    public function casLogin()
    {
        $field = ['code', 'open_id'];
        $param = $this->_apiParam($field);
        checkData($param, [
            'code' => 'require',
            'open_id' => 'require',
        ]);

        $user = User::where('cas_open_id', $param['open_id'])->find();
        $key = 'casLogin:' . ($user['id'] ?? 0);
        if (empty($user) || !Cache::has($key)) {
            $res = (new CasHelper())->getUserInfo($param['code']);
            $user = User::where('appid', $res['username'])->find();
            if (empty($user)) {
                User::insert([
                    'title' => $res['title'],
                    'appid' => $res['username'],
                    'appsecret' => User::translatePassword(config('app.base_account.appsecret')),
                    'is_admin' => $res['is_admin'],
                    'cas_open_id' => $param['open_id'],
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                $user = User::where('appid', $res['username'])->find();
            } else {
                User::where('appid', $res['username'])->update([
                    'title' => $res['title'],
                    'cas_open_id' => $param['open_id'],
                    'update_time' => time(),
                ]);
            }
            Cache::set($key, 1, 60 * 60); //一个小时允许更新一次
        }

        $jwt = new JwtTool();
        $info = $jwt->jsonReturnToken(['uid' => $user['id']]);
        unset($user['appsecret']);
        unset($user['cas_open_id']);
        $info['user_info'] = json_encode($user, 256);

        return redirect('/?' . http_build_query($info));
    }
}
