<?php

namespace app\model\optionGroup\roomOptionGroup;

use think\Model;

class RoomOptionGroup extends Model
{
    protected $table = 'room_option_group';

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

    public function roomOptionGroupOption()
    {
        return $this->hasMany(RoomOptionGroupOption::class, 'option_group_id');
    }
    
}