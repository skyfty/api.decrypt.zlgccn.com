<?php

namespace app\model;

class City extends BaseModel
{
    protected $table = 'city';
    protected $hidden = [ 'project_id', 'create_time', 'update_time'];
    
    // 房间关联
    public function rooms()
    {
        return $this->hasMany(Room::class, 'cityId')->order('sort asc');
    }

}