<?php

namespace app\model\ButtonPoint;
use think\Model;

class ButtonPointChapter extends Model
{
    protected $table = 'button_point_chapter';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = [ 'id', 'content', 'background', 'create_time', 'update_time'];

}