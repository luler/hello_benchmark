<?php
/**
 * Created by PhpStorm.
 * 
 * Date: 2018/10/12
 * Time: 10:21
 */

namespace app\common\traits;

/*
@param string $message 获取返回提示语
 */

trait ApiReturn
{
    protected $returnMessage = 'success';
    protected $returnCode = '200';
    protected $returnInfo = [];
    protected $returnMethod = 'json';

    public function setReturnMessage($message)
    {
        $this->returnMessage = $message;
        return $this;
    }

    public function setReturnCode($code)
    {
        $this->returnCode = $code;
        return $this;
    }

    public function setReturnInfo($data = [])
    {
        $this->returnInfo = $data;
        return $this;
    }

    public function setReturnMethod($method)
    {
        $this->returnMethod = $method;
        return $this;
    }

    /**
     * 成功返回操作，已默认code=200
     * @param string $message
     * @param array $info
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function successResponse($message = 'success', $info = [])
    {
        return $this->setReturnCode(200)->setReturnMessage($message)->setReturnInfo($info)->returnDo();
    }

    /**
     * 资源创建成功返回
     * Author:我只想看看蓝天<1207032539@qq.com>
     * @param string $message
     * @param array $info
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function createdResponse($message = 'success', $info = [])
    {
        return $this->setReturnCode(201)->setReturnMessage($message)->setReturnInfo($info)->returnDo();
    }

    /**
     * 资源删除后无内容返回
     * Author:我只想看看蓝天<1207032539@qq.com>
     * @param string $message
     * @param array $info
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function noContentResponse()
    {
        return response()->code(204);
    }

    /**
     * jsonp返回操作，已默认code=200
     * @param string $message
     * @param array $info
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function jsonpResponse($code = 200, $message = 'success', $info = [])
    {
        return $this->setReturnMethod('jsonp')->setReturnCode($code)->setReturnMessage($message)->setReturnInfo($info)->returnDo();
    }

    /**
     * 常规失败返回操作，已默认code=400
     * @param string $message 提示语
     * @param array $info 返回数据
     * @return \think\response\Json|\think\response\Jsonp
     * @author klinson <klinson@163.com>
     */
    public function errorResponse($message = 'error', $info = [])
    {
        return $this->setReturnCode(400)->setReturnMessage($message)->setReturnInfo($info)->returnDo();
    }

    /**
     * 通用返回
     * Author:我只想看看蓝天<1207032539@qq.com>
     * @param int $code
     * @param string $message
     * @param array $info
     * @return \think\response\Json|\think\response\Jsonp
     */
    public function commonResponse($code = 200, $message = 'success', $info = [])
    {
        return $this->setReturnCode($code)->setReturnMessage($message)->setReturnInfo($info)->returnDo();
    }

    /**
     * 返回接口数据
     * @return \think\response\Json|\think\response\Jsonp
     * @author klinson <klinson@163.com>
     */
    public function returnDo()
    {
        //设置返回内容
        $return = [
            'message' => $this->returnMessage,
            'code' => (int)$this->returnCode,
            'info' => $this->returnInfo
        ];

        switch ($this->returnMethod) {
            case 'jsonp':
                return jsonp($return)->code($this->returnCode);
            default:
                return json($return)->code($this->returnCode);
        }
    }
}
