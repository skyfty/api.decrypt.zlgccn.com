<?php
// app/model/StoryPoint.php
namespace app\model\ProjectStoryLine;

use think\Model;
use app\model\Room;
use app\model\Project;

class ProjectStoryLine extends Model
{
    protected $name = 'project_story_lines';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 定义关联
    public function projectStoryLineCondition()
    {
        return $this->hasMany(ProjectStoryLineCondition::class, 'project_story_line_id');
    }
    
    public function projectStoryLineAction()
    {
        return $this->hasMany(ProjectStoryLineAction::class, 'project_story_line_id');
    }
    
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}