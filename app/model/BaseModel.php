<?php

namespace app\model;

use think\Model;

class BaseModel extends Model
{
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 设置JSON字段
    protected $json = [];
    
    // 设置JSON类型字段
    protected $jsonAssoc = true;
}