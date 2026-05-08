<?php
// app/model/ActionAssignVariable.php
namespace app\model\StoryPoint;

use think\Model;

class ActionRequiredCondition extends Model
{
    protected $name = 'action_required_conditions';
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    }
}