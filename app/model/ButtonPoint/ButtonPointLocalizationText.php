<?php

namespace app\model\ButtonPoint;
use think\Model;

class ButtonPointLocalizationText extends Model
{
    protected $table = 'button_point_localizationText';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
    
    

}