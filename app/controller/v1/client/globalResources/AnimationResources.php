<?php

declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

use think\facade\Request;
use think\facade\Db;

class AnimationResources
{

    /**
     * 获取某个项目的所有动画
     * GET /api/v1/GlobalResources/GetAnimations
     *
     * 参数：
     *   project_id   string  必选
     */
    public function GetAnimations()
    {
        $project_id = Request::param('project_id', '');
        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }

        $list = Db::name('animations')
            ->where('project_id', $project_id)
            ->withoutField(['project_id', 'sort', 'create_time', 'update_time'])
            ->select()
            ->toArray();
            
        foreach ($list as &$item) {
            $item['loop'] = (bool)$item['loop'];
            $item['isPlaying'] = (bool)$item['isPlaying'];
        }
        unset($item);


        return success($list, '获取成功');
    }


    /**
     * 获取某个动画的所有动作
     * GET /api/v1/GlobalResources/GetAnimationFrames
     *
     * 参数：
     *   animation_id  int  必选
     */
    public function GetAnimationActions()
    {
        $animation_id = (int) Request::param('animation_id', 0);
        if ($animation_id <= 0) {
            return error('动画ID不能为空', 400);
        }

        $list = Db::name('animation_actions')
            ->where('animation_id', $animation_id)
            ->withoutField(['animation_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();
            
        foreach ($list as &$item) {
            $animationFramesList = Db::name('animation_frames')
                ->where('animation_action_id', $item['id'])
                ->order('sort', 'asc')
                ->withoutField(['animation_action_id', 'sort', 'create_time', 'update_time'])
                ->select()
                ->toArray();
            $item['animationFramesList'] = $animationFramesList;
        }
        unset($item);

        return success($list, '获取成功');

    }

    /**
     * 获取某个动作的所有帧
     * GET /api/v1/GlobalResources/GetAnimationFrames
     *
     * 参数：
     *   animation_id  int  必选
     */
    public function GetAnimationFrames()
    {
        $animation_id = (int) Request::param('animation_action_id', 0);
        if ($animation_id <= 0) {
            return error('动作ID不能为空', 400);
        }

        $list = Db::name('animation_actions')
            ->where('id', $animation_id)
            ->withoutField(['animation_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();
            
        foreach ($list as &$item) {
            $animationFramesList = Db::name('animation_frames')
                ->where('animation_action_id', $item['id'])
                ->order('sort', 'asc')
                ->withoutField(['animation_action_id', 'sort', 'create_time', 'update_time'])
                ->select()
                ->toArray();
            $item['animationFramesList'] = $animationFramesList;
        }
        unset($item);

        return success($list, '获取成功');

    }

}
