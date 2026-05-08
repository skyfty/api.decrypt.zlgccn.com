<?php
// app/model/StoryPoint/PalyRoleDialogue.php
namespace app\model\StoryPoint;

use think\Model; 

class ActionRoleDialogue extends Model
{
    protected $name = 'action_role_dialogue';

    protected $type = [
        'character_image' => 'integer',
        'expression_image' => 'integer',
        'has_options' => 'boolean',
    ];
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    } 
}