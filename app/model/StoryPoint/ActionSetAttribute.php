<?php
namespace app\model\StoryPoint;

use think\Model;

class ActionSetAttribute extends Model
{
    protected $name = 'action_set_attribute';
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    }
}