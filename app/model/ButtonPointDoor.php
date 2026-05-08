<?php

namespace app\model;

class ButtonPointDoor extends BaseModel
{
    protected $table = 'button_point_door';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'button_point_id' => 'integer',
        'doorCityId'      => 'integer',
        'doorRoomId'      => 'integer',
        'successVoice'    => 'integer',
        'errorVoice'      => 'integer',
        'isOpen'          => 'boolean',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
}