<?php

namespace app\model;

class AnimationAction extends BaseModel
{
    protected $table = 'animation_frames';

    protected $pk = 'id';

    protected $type = [
        'id'        => 'integer',
        'path'      => 'string',
        'width'     => 'integer',
        'height'    => 'integer',
        'size'      => 'integer',
    ];
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

    /**
     * 直接查询（更高效）
     */
    public static function getAnimationActionById($animationActionId, $scale = 100)
    {
        if (!$animationActionId) {
            return [];
        }

        $animationAction = self::where('animation_action_id', $animationActionId)
            ->field('frameImage')
            ->select()->toArray();
        if ($scale) {
            foreach ($animationAction as &$action) {
                $action['frameImage'] = $action['frameImage'] . '?scale=' . $scale;
            }
        }
        return $animationAction ?: [];
    }
}
