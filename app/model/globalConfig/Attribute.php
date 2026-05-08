<?php

namespace app\model\globalConfig;

use think\Model;

class Attribute extends Model
{
    protected $table = 'project_attribute';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
    ];

    protected $hidden = [ 'create_time', 'update_time'];

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 创建时间字段名
    protected $createTime = 'create_time';

    // 更新时间字段名  
    protected $updateTime = 'update_time';
    
}