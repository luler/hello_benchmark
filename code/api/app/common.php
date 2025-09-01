<?php
// 应用公共文件

/**
 * 判断登陆，获取uid
 * @return bool|mixed
 * @author 我只想看看蓝天 <1207032539@qq.com>
 */
function is_login()
{
    //返回登录用户的uid
    if (request()->__uid__) {
        return request()->__uid__;
    } else {
        try {
            $authInfo = \app\common\tool\JwtTool::instance()->authenticate();
            return $authInfo['uid'];
        } catch (\Exception $e) {
            return false;
        }
    }
}

/**
 * 检查数据
 * @param $param
 * @param $rule
 * @param $message
 * @author 我只想看看蓝天 <1207032539@qq.com>
 */
function checkData($param, $rule, $message = [])
{
    $check = validate($rule, $message, false, false);
    if (!$check->check($param)) {
        throw new \app\common\exception\CommonException($check->getError());
    }
}

/**
 * 监听执行的sql
 * @param bool $is_block
 * @author 我只想看看蓝天 <1207032539@qq.com>
 */
function sql_dump($is_block = true)
{
    \think\facade\Db::startTrans(); //调试方法，防止增删改sql的执行，但插入sql还是会导致自增id会增加
    \think\facade\Db::listen(function ($sql, $time) use ($is_block) {
        $info = $sql . ' [耗时:' . ($time * 1000) . 'ms]';
        $is_block && stripos($sql, 'COLUMNS FROM') === false ? halt($info) : dump($info);
    });
}
