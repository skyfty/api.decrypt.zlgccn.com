<?php
namespace app\model\StoryPoint\RoomStoryPointCondition;

use think\Model;

class ConditionAttribute extends Model
{
    protected $name = 'condition_attribute';
    
    protected $hidden = ['create_time', 'update_time'];
}