<?php

namespace app\model\HintPoint;
use think\Model;

class HintPointNumber extends Model
{
    protected $table = 'hint_point_number';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'hint_Point_id'   => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}