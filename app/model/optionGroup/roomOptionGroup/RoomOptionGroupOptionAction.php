<?php

namespace app\model\optionGroup\roomOptionGroup;

use think\Model;

class RoomOptionGroupOptionAction extends Model
{
    protected $table = 'room_option_group_option_action';

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
    
    public function roomOptionGroupOptionActionMsg()
    {
        return $this->hasOne(RoomOptionGroupOptionActionMsg::class, 'option_action_id');
    }
    
    public function roomOptionGroupOptionActionModifyStoryValue()
    {
        return $this->hasOne(RoomOptionGroupOptionActionModifyStoryValue::class, 'option_action_id');
    }
    
}