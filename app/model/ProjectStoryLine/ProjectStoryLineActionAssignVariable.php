<?php
// app/model/StoryPoint.php
namespace app\model\ProjectStoryLine;

use think\Model;

class ProjectStoryLineActionAssignVariable extends Model
{
    protected $name = 'project_action_assign_variables';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
     
}
