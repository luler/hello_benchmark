<?php

namespace app\common\exception;

use app\common\traits\ApiReturn;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\RouteNotFoundException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    use ApiReturn;
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制

        if (
            $e instanceof CommonException
            || $e instanceof UnauthorizedHttpException
            || $e instanceof ForbiddenException
            || $e instanceof RouteNotFoundException
        ) {
            if ($e instanceof RouteNotFoundException) {
                return $this->commonResponse(404, '路由不存在');
            }
            return $this->commonResponse($e->getCode(), $e->getMessage());
        }

        return $this->commonResponse(500, $this->systemErrorMessage($e->getMessage()));

        // 其他错误交给系统处理(api不需要页面信息，任何情况都返回json数据)
//        return parent::render($request, $e);
    }

    /**
     * 错误信息捕获
     * @param $message
     * @return string
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function systemErrorMessage($message)
    {
        if (!app()->isDebug()) {
            $message = '服务器繁忙';
        }
        return $message;
    }
}
