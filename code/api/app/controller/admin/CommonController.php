<?php

namespace app\controller\admin;

use app\ApiBaseController;

class CommonController extends ApiBaseController
{
    public function test()
    {
        return $this->successResponse('admin-成功访问');
    }
}
