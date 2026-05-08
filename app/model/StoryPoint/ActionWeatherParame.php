<?php
// app/model/ActionOperateButtonPoint.php
namespace app\model\StoryPoint;

use think\Model; 

class ActionWeatherParame extends Model
{
    protected $name = 'action_weather_parame';
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    } 
}