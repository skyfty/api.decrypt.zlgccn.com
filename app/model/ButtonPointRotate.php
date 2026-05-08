<?php

namespace app\model;

class ButtonPointRotate extends BaseModel
{
    protected $table = 'button_point_rotate';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'button_point_id' => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}