<?php
// app/model/StoryPoint.php
namespace app\model\StoryPoint;

use think\Model;
// use app\model\Room;
// use app\model\Project;

class StoryPoint extends Model
{
    protected $name = 'story_points';
    
    // 设置字段自动转换类型
    protected $type = [
        'status'       => 'boolean',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 定义关联
    public function conditions()
    {
        return $this->hasMany(StoryPointCondition::class, 'story_point_id');
    }
    
    public function actions()
    {
        return $this->hasMany(StoryPointAction::class, 'story_point_id');
    }
}