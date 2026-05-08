<?php

namespace app\model\Trans;

use think\Model;
class TranslationKeyword extends Model
{
    protected $table = 'translation_keyword';
    
    protected $pk = 'id';
    
    protected $type = [
        'id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];

    public static function queryKeyByName($Key){
        
        $Chinese = self::where('Key', $Key)->value('Chinese');
        return $Chinese;
    }
}