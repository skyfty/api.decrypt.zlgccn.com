<?php
// app/model/StoryPoint/ActionChapter.php
namespace app\model\StoryPoint;

use think\Model; 

class ActionChapter extends Model
{
    protected $name = 'action_chapter';

    protected $type = [
        'character_image' => 'integer',
    ];
    
    public function action()
    {
        return $this->belongsTo(StoryPointAction::class, 'action_id');
    } 
}