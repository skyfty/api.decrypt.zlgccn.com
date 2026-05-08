<?php

namespace app\model\HintPoint;
use think\Model;
use app\model\Image;

class HintPointImage extends Model
{
    protected $table = 'hint_point_image';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'              => 'integer',
        'hint_Point_id'   => 'integer',
        'image_id'        => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];
    
    // 图片关联
    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }
}