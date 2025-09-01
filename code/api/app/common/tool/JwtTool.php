<?php

namespace app\common\tool;

use app\common\exception\SystemErrorException;
use app\common\exception\UnauthorizedHttpException;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Config;

class JwtTool
{
    /**
     * 过期时间秒数
     *
     * @var int
     */
    private static $expires = 7200;//两个小时

    private static $instance;//实例

    /**
     * The header name.
     *
     * @var string
     */
    private static $token_key = 'Authorization';

    /**
     * 单例模式
     * @return JwtTool
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 解密
     * @return mixed
     * @throws SystemErrorException
     * @throws UnauthorizedHttpException
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    final function authenticate()
    {
        return $this->certification($this->getToken());
    }

    /**
     * 获取客户端信息
     * @return mixed
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    private function getToken()
    {
        $token = \request()->header(self::$token_key);
        if (empty($token)) {
            $token = \request()->param(self::$token_key);
            if (empty($token)) {
                throw new UnauthorizedHttpException('登录凭证无效');
            }
        }
        return $token;
    }

    /**
     * @param string $token
     * @return mixed
     * @throws SystemErrorException
     * @throws UnauthorizedHttpException
     */
    public function certification($token = '')
    {
        $res = $this->verification($token);
        return $res['data'];
    }

    /**
     * 签发token
     * @param $data
     * @param int $expires
     * @return string
     * @throws SystemErrorException
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    private function IssueToken($data, $expires = 0)
    {
        if (empty($expires)) {
            $expires = $this->getExpires();
        }
        $key = $this->getKey();
        $time = time(); //当前时间
        $token = [
            'iss' => 'luler', //签发者 可选
            'aud' => '', //接收该JWT的一方，可选
            //不用下面两个参数了，因为分布式服务器时间不一致，签发机器和验证机器时间差可能导致token不可用
//            'iat' => $time, //签发时间
//            'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time + $expires, //过期时间
            'data' => $data
        ];
        return JWT::encode($token, $key);
    }

    /**
     * 验证token
     * @param $jwt
     * @return array|mixed
     * @throws SystemErrorException
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    private function verification($jwt)
    {
        $logout_key = 'logout:' . $jwt;
        if (Cache::has($logout_key)) {
            throw new UnauthorizedHttpException('登录凭证已失效');
        }
        $key = $this->getKey();
        try {
            $decoded = (array)JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $decoded = json_decode(json_encode($decoded), true);
        } catch (\Exception $e) { //过期处理
            throw new UnauthorizedHttpException('登录凭证解析失败');
        }
        return $decoded;
    }

    /**
     * @throws SystemErrorException
     *
     */
    private function getKey()
    {
        $jwt_secret = Config::get('jwt.jwt_secret');
        if (empty($jwt_secret)) {
            throw new SystemErrorException('jwt配置有误');
        }
        return $jwt_secret;
    }

    /**
     * 获取有效期
     * @return int|mixed
     */
    private function getExpires()
    {
        return (int)Config::get('jwt.auth_expires', self::$expires);
    }

    /**
     * @param $uid
     * @return array
     * @throws SystemErrorException
     */
    public function jsonReturnToken($data = [])
    {
        if (empty($data)) {//防止为空的情况
            throw new UnauthorizedHttpException('签发信息异常');
        }
        return [
            'access_token' => $this->IssueToken($data),
            'expires_in' => $this->getExpires()
        ];
    }

    /**
     * 退出登录
     * @param $authorization
     * @return bool
     */
    public function logout($jwt)
    {
        $key = 'logout:' . $jwt;
        Cache::set($key, date('Y-m-d H:i:s'), $this->getExpires());
        return true;
    }
}