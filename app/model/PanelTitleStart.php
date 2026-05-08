<?php

namespace app\model;

class PanelTitleStart extends BaseModel
{
    protected $table = 'panel_title_start';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer',
        'panel_title_item_id'  => 'integer',
        'city_id'              => 'integer',
        'room_id'              => 'integer',
        'success_audio'        => 'integer',
        'error_audio'          => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['panel_title_item_id', 'create_time', 'update_time'];
    
    // 面板标题项关联
    public function panelTitleItem()
    {
        return $this->belongsTo(PanelTitleItem::class, 'panel_title_item_id');
    }

    // 获取器 - doorCityId (映射 city_id)
    public function getDoorCityIdAttr($value)
    {
        return $this->city_id;
    }
    
    // 获取器 - doorRoomId (映射 room_id)
    public function getDoorRoomIdAttr($value)
    {
        return $this->room_id;
    }
    
    // 获取器 - successVoice (映射 success_audio 的文件路径)
    public function getSuccessVoiceAttr($value)
    {
        return $this->success_audio ? Audio::getAudioUrlById($this->success_audio) : '';
    }
    
    // 获取器 - errorVoice (映射 error_audio 的文件路径)
    public function getErrorVoiceAttr($value)
    {
        return $this->error_audio ? Audio::getAudioUrlById($this->error_audio) : '';
    }
    
    // 追加字段
    protected $append = ['door_city_id', 'door_room_id', 'success_voice', 'error_voice'];
    
    // 重写 toArray 方法确保字段映射正确
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // 手动设置映射字段
        $array['doorCityId'] = $this->city_id;
        $array['doorRoomId'] = $this->room_id;
        $array['successVoice'] = $this->success_audio ? Audio::getAudioUrlById($this->success_audio) : '';
        $array['errorVoice'] = $this->error_audio ? Audio::getAudioUrlById($this->error_audio) : '';
        
        // 移除原始字段（如果还在的话）
        unset($array['city_id'], $array['room_id'], $array['success_audio'], $array['error_audio']);
        unset($array['door_city_id'], $array['door_room_id'], $array['success_voice'], $array['error_voice']);
        
        return $array;
    }
    
    // 或者使用序列化方法
    public function serialize(\think\Model $model)
    {
        return [
            'id' => $model->id,
            'doorCityId' => $model->city_id,
            'doorRoomId' => $model->room_id,
            'successVoice' => $model->success_audio ? Audio::getAudioUrlById($model->success_audio) : '',
            'errorVoice' => $model->error_audio ? Audio::getAudioUrlById($model->error_audio) : '',
        ];
    }

}