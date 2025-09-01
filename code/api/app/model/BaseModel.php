<?php

namespace app\model;

use think\Model;

class BaseModel extends Model
{
    public $autoWriteTimestamp = 'int';
    protected $defaultSoftDelete = 0;
}
