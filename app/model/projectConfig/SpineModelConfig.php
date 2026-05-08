<?php
namespace app\model\projectConfig;

use think\Model;

class SpineModelConfig extends Model
{
    protected $table = 'spine_model_config';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
        'animations' => 'array',
        'alpha' => 'boolean',
        'premultiplied_alpha' => 'boolean',
        'preserve_drawing_buffer' => 'boolean',
        'show_controls' => 'boolean',
        'status' => 'boolean',
    ];

    protected $hidden = [ 'create_time', 'update_time'];

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 创建时间字段名
    protected $createTime = 'create_time';

    // 更新时间字段名  
    protected $updateTime = 'update_time';
    
}