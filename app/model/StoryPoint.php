<?php

namespace app\model;

use think\Model;

class StoryPoint extends Model
{
    protected $table = 'story_points'; // 如表名不是 story_point

    // 定义关联：一个剧情点拥有多个条件
    // public function conditions()
    // {
    //     return $this->hasMany(StoryPointCondition::class, 'story_point_id', 'id');
    // }

    // 定义关联：一个剧情点拥有多个动作
    // public function actions()
    // {
    //     return $this->hasMany(StoryPointAction::class, 'story_point_id', 'id');
    // }

    /**
     * 获取某个房间内的所有剧情点，并带上关联数据
     */
    public static function getFormattedStoryPoints($roomId)
    {
        if (empty($roomId)) return [];

        $storyPoints = self::with([
            'conditions',
            'actions.assignVariable',      // 假设有关联模型 StoryPointActionAssignVariable
            'actions.operateButtonPoint',  // 假设有关联模型 StoryPointActionOperateButtonPoint
            'actions.requiredConditions',  // 假设有关联模型 StoryPointActionRequiredCondition
        ])
        ->where('room_id', $roomId)
        ->select();

        // 🎯 这里假设有一个 StoryPointController 中的 formatStoryPoint 方法用于格式化输出
        // 但由于我们是在 Model 层，不能直接调用 Controller，
        // 所以建议：格式化逻辑要么在 Service，要么提供一个 format() 方法在 Model 中

        $list = [];
        foreach ($storyPoints as $point) {
            // 这里只是临时直接返回对象，您需要自己实现格式化逻辑，或调用 Controller 方法
            // 或者：将 formatStoryPoint() 方法移至 Model 或新建 StoryPointFormatService
            $list[] = $point;
        }

        return $list;
    }
}