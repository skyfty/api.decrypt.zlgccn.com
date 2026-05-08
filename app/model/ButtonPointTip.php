<?php

namespace app\model;

class ButtonPointTip extends BaseModel
{
    protected $table = 'button_point_tip';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'button_point_id' => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}