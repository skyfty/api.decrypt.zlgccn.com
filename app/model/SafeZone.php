<?php

namespace app\model;

class SafeZone extends BaseModel
{
    protected $table = 'SafeZone';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'         => 'integer',
        'projectId'  => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['projectId', 'name', 'create_time', 'update_time'];
}