<?php

namespace app\model;

class RoomStoryVariables extends BaseModel
{
    protected $table = 'room_story_variables';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'      => 'integer',
        'room_id' => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['room_id', 'create_time', 'update_time'];
    
    // 房间关联
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}