<?php

namespace app\model\optionGroup\roomOptionGroup;

use think\Model;

class RoomOptionGroupOptionActionMsg extends Model
{
    protected $table = 'room_option_group_option_action_msg';

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