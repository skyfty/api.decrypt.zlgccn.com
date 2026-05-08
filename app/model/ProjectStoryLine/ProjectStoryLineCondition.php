<?php
// app/model/StoryPoint.php
namespace app\model\ProjectStoryLine;

use think\Model;

class ProjectStoryLineCondition extends Model
{
    protected $name = 'project_story_line_conditions';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
}