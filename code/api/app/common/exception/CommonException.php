<?php
/**
 * Created by PhpStorm.
 * 
 * Date: 2018/10/12
 * Time: 16:09
 */

namespace app\common\exception;

use think\Exception;
use Throwable;

class CommonException extends Exception
{
    public function __construct($message = "", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}