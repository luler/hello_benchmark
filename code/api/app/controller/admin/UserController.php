<?php

namespace app\controller\admin;

use app\ApiBaseController;
use app\model\User;

class UserController extends ApiBaseController
{
    /**
     * 获取用户信息
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function getUserInfo()
    {
        $res = User::find(is_login());
        unset($res['appsecret']);
        return $this->successResponse('获取成功', $res);
    }

    /**
     * 修改密码
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     */
    public function changPassword()
    {
        $field = ['password', 'confirm_password'];
        $param = $this->_apiParam($field);
        checkData($param, [
            'password|新密码' => 'require|min:6',
            'confirm_password|确认密码' => 'require|confirm:password',
        ]);
        User::update([
            'id' => is_login(),
            'appsecret' => User::translatePassword($param['password']),
        ]);
        return $this->successResponse('修改成功');
    }
}
