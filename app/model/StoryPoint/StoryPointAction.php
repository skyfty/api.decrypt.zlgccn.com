<?php
namespace app\model\StoryPoint;

use think\Model;

class StoryPointAction extends Model
{
    protected $name = 'story_point_actions';
    
    protected $hidden = ['create_time', 'update_time'];
    
    public function storyPoint()
    {
        return $this->belongsTo(StoryPoint::class, 'story_point_id');
    }
    
    public function assignVariable()
    {
        return $this->hasOne(ActionAssignVariable::class, 'action_id');
    }
    
    public function operateButtonPoint()
    {
        return $this->hasOne(ActionOperateButtonPoint::class, 'action_id');
    }
    public function animationPlayMode()
    {
        return $this->hasOne(ActionAnimationPlayMode::class, 'action_id');
    }
    public function updateWeather()
    {
        return $this->hasOne(ActionWeatherParame::class, 'action_id');
    }
    public function roleDialogue()
    {
        return $this->hasOne(ActionRoleDialogue::class, 'action_id');
    }
    public function chapter()
    {
        return $this->hasOne(ActionChapter::class, 'action_id');
    }
    public function setAttribute()
    {
        return $this->hasOne(ActionSetAttribute::class, 'action_id');
    }


    public function requiredCondition()
    {
        return $this->hasOne(ActionRequiredCondition::class, 'action_id');
    }
    
    public function requiredConditions()
    {
        return $this->belongsToMany(
            StoryPointCondition::class, 
            'action_required_conditions',
            'condition_id',
            'action_id'
        );
    }
}