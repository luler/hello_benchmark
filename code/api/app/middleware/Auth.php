<?php
declare (strict_types=1);

namespace app\middleware;

use app\common\tool\JwtTool;

class Auth
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $data = JwtTool::instance()->authenticate();

        $request->__uid__ = $data['uid'];

        return $next($request);
    }
}
