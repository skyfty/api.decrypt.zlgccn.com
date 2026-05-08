<?php

namespace app\model;

class ButtonPointItem extends BaseModel
{
    protected $table = 'button_point_item';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'button_point_id' => 'integer',
        'itemsType'           => 'string',// 物品类型 Preview、PickUp
        'itemsID'         => 'int',
        'itemCount'       => 'int',
        'items'         => 'int',
        'zoomRatio'       => 'int',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
    

}