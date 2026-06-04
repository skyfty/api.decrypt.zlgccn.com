<?php

namespace app\model;

class ButtonPointGroup extends BaseModel
{
    protected $table = 'button_point_group';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
        'room_id' => 'integer',
        'sort' => 'integer',
        'hidden' => 'integer',
        'locked' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function buttonPoints()
    {
        return $this->hasMany(ButtonPoint::class, 'button_point_group_id')->order('sort', 'asc');
    }
}