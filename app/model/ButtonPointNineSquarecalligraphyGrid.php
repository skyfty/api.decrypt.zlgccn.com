<?php

namespace app\model;

class ButtonPointNineSquarecalligraphyGrid extends BaseModel
{
    protected $table = 'button_point_nineSquarecalligraphyGrid';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'button_point_id' => 'integer',
        'paddingImage'    => 'integer',
        'compoundImage'   => 'integer',
        'blankGrid'       => 'boolean',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}