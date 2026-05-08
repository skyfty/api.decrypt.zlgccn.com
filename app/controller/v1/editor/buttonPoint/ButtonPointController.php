<?php

declare(strict_types=1);

namespace app\controller\v1\editor\buttonPoint;

use think\facade\Db;

class ButtonPointController
{
    
    /**
     * 获取 ButtonPoint 详情
     */
    public function detail()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        $id = request()->param('id');

        $buttonPoint = Db::name('button_point')
            ->where('id', $id)
            ->find();
        if (empty($buttonPoint))  return error('数据不存在.');

        if ($buttonPoint['sub_resource_type'] === 2) {
            $animationActionId = $buttonPoint['animation_action'];

            $frameRecord = Db::table('animation_frames')
                ->where('animation_action_id', $animationActionId)
                ->field('frameImage')
                ->order('sort', 'asc')
                ->find();

            // 如果 $frameRecord 是 null，或者没有 'frameImage' 键，则使用空字符串
            $buttonPoint['animation_action_path'] =
                ($frameRecord && isset($frameRecord['frameImage']))
                ? $frameRecord['frameImage']
                : '';
            $buttonPoint['hidden'] = false;
        }

        $table = null;
        switch ($buttonPoint['type']) {
            case 1:
                $table = 'button_point_tip';
                break;
            case 2:
                $table = 'button_point_draggable';
                break;
            case 3:
                $table = 'button_point_rotate';
                break;
            case 4:
                $table = 'button_point_move';
                break;
            case 5:
                $table = 'button_point_nineSquarecalligraphyGrid';
                break;
            case 9:
                $table = 'button_point_door';
                break;
            case 10:
                $table = 'button_point_item';
                break;
        }

        if ($table) {
            $param = Db::table($table)
                ->where('button_point_id', $buttonPoint['id'])
                ->find();
            $buttonPoint['param'] = $param;
        }
        return success($buttonPoint);
    }
}
