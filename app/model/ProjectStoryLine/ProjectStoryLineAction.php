<?php
// app/model/StoryPoint.php
namespace app\model\ProjectStoryLine;

use think\Model;

class ProjectStoryLineAction extends Model
{
    protected $name = 'project_story_line_actions';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    function ProjectStoryLineActionAssignVariable(){
        return $this->hasOne(ProjectStoryLineActionAssignVariable::class, 'project_story_line_action_id', 'id');
    }

}