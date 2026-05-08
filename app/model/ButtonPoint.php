<?php

namespace app\model;

use app\model\ButtonPoint\ButtonPointChapter;
use app\model\ButtonPoint\ButtonPointLocalizationText;
class ButtonPoint extends BaseModel
{
    protected $table = 'button_point';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'               => 'integer',
        'room_id'          => 'integer',
        'resource_id'      => 'integer',
        'image_id'         => 'integer',
        'resource_type'    => 'integer',
        'sub_resource_type'=> 'integer',
        'type'             => 'integer',
        'wxSafeArea'       => 'boolean',
        'multiLanguage'    => 'boolean',
        'sort'             => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = [ 'name', 'image_id', 'create_time', 'update_time'];
    
    // 房间关联
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
    public function localizationText()
    {
        return $this->hasOne(ButtonPointLocalizationText::class, 'button_point_id');
    }
    
    // 音频资源关联
    public function audioResources()
    {
        return $this->hasMany(ButtonPointResourcesAudio::class, 'buttonPoint_id');
    }
    
    // 根据类型获取参数
    public function getParamAttribute()
    {
        $typeToTableMap = [
            1 => ButtonPointTip::class,
            2 => ButtonPointDraggable::class,
            3 => ButtonPointRotate::class,
            4 => ButtonPointMove::class,
            5 => ButtonPointNineSquarecalligraphyGrid::class,
            9 => ButtonPointDoor::class,
            10 => ButtonPointItem::class,
            11 => ButtonPointChapter::class
        ];
        
        $modelClass = $typeToTableMap[$this->type] ?? null;
        
        if (!$modelClass) {
            return null;
        }
        
        return $modelClass::where('button_point_id', $this->id)->find();
    }
}