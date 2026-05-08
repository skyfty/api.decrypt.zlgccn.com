<?php

namespace app\model\HintPoint;
use think\Model; 

class HintPointLetters extends Model
{
    protected $table = 'hint_point_letters';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'hint_Point_id'   => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}