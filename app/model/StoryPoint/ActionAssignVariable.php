<?php
// app/model/ActionAssignVariable.php
namespace app\model\StoryPoint;

use think\Model;

class ActionAssignVariable extends Model
{
    protected $name = 'action_assign_variables';
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    }
}