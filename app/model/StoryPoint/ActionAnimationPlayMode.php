<?php
// app/model/ActionOperateButtonPoint.php
namespace app\model\StoryPoint;

use think\Model;
use app\model\ButtonPoint;

class ActionAnimationPlayMode extends Model
{
    protected $name = 'action_animation_play_mode';
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    }
    
    public function buttonPoint()
    {
        return $this->belongsTo(ButtonPoint::class, 'button_point_id');
    }
}