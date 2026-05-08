<?php

namespace app\model;

class Audio extends BaseModel
{
    protected $table = 'audio';
    
    protected $pk = 'id';
    
    protected $type = [
        'id' => 'integer',
        'file' => 'string',
    ];

    protected $hidden = ['create_time', 'update_time'];

    public static function getAudioById($id)
    {
        return self::where('id', $id)->find();
    }

    
    // 获取音频URL
    public static function getAudioUrlById($id)
    {
        if (!$id) {
            return '';
        }
        
        $audio = self::find($id);
        return $audio ? $audio->file : '';
    }

}