<?php
namespace app\model;

use app\model\StoryPoint\ActionOperateButtonPoint;
use think\Model;

class ButtonPoint extends Model
{
    protected $name = 'button_point';
    
    public function actionOperateButtons()
    {
        return $this->hasMany(ActionOperateButtonPoint::class, 'button_point_id');
    }
}