<?php

namespace app\model;

class ButtonPointResourcesAudio extends BaseModel
{
    protected $table = 'button_point_resources_audio';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'             => 'integer',
        'buttonPoint_id' => 'integer',
        'resource_id'    => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['buttonPoint_id', 'create_time', 'update_time'];
    
    
    // 关联音频资源
    public function audio()
    {
        return $this->belongsTo(Audio::class, 'resource_id');
    }
    
    // 定义访问器获取音频路径
    public function getAudioPathAttribute()
    {
        if ($this->audio) {
            // 假设 Audio 模型中有 path 或类似的字段存储路径
            return $this->audio->file ?? '';
        }
        return '';
    }

}