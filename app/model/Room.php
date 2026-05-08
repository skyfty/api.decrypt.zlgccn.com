<?php

namespace app\model;
use app\model\HintPoint\HintPoint;

class Room extends BaseModel
{
    protected $table = 'room';

    // buttonPoint关联
    public function buttonPoints()
    {
        return $this->hasMany(ButtonPoint::class, 'room_id')->order('sort asc');
    }

    // hintPoint关联
    public function hintPoints()
    {
        return $this->hasMany(HintPoint::class, 'room_id')->order('sort asc');
    }

    // storyPoint关联
    public function storyPoints()
    {
        return $this->hasMany(StoryPoint::class, 'room_id');
    }


    // roomStoryVariables关联
    public function storyVariablesList()
    {
        return $this->hasMany(RoomStoryVariables::class, 'room_id');
    }

    // 查询背景音频关联
    public function queryBackgroundAudio()
    {
        return $this->hasOne(Audio::class, 'id', 'background_audio')->bind(['background_audio_file' => 'file']);
    }
}
