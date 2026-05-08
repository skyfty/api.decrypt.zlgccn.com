<?php
// app/model/ActionAssignVariable.php
namespace app\model\StoryPoint;

use think\Model;

class ConditionStoryVariables extends Model
{
    protected $name = 'condition_attribute';

    protected $hidden = ['create_time', 'update_time'];
    
    public function storyVariables()
    {
        return $this->belongsTo(StoryPointCondition::class, 'condition_id');
    }
}