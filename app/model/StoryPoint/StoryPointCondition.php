<?php
namespace app\model\StoryPoint;

use app\model\StoryPoint\RoomStoryPointCondition\ConditionStoryVariable;
use app\model\StoryPoint\RoomStoryPointCondition\ConditionAttribute;

use think\Model;

class StoryPointCondition extends Model
{
    protected $name = 'story_point_conditions';

    protected $hidden = ['create_time', 'update_time'];
    
    public function storyPoint()
    {
        return $this->belongsTo(StoryPoint::class, 'story_point_id');
    }

    public function storyVariable()
    {
        return $this->hasOne(ConditionStoryVariable::class, 'condition_id');
    }

    public function attribute()
    {
        return $this->hasOne(ConditionAttribute::class, 'condition_id');
    }

}