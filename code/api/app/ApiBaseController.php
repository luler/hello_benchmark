<?php
declare (strict_types=1);

namespace app;

use app\common\traits\ApiReturn;

class ApiBaseController extends BaseController
{
    use ApiReturn;

    /**
     * 获取指定请求参数
     * @param array $fields
     * @return mixed
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function _apiParam($fields = [])
    {
        $param = $this->request->param($fields);
        return $param;
    }
}
