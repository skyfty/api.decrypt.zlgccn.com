<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;
use app\model\Image;

class TransitionScene
{
    /**
     * 获取指定项目的转场
     * GET /v1/editor/panel/GetTransitionScene
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetTransitionScene()
    {
        $project_id = (int) Request::param('project_id', 0);
        $scale = (int) Request::param('scale', 100);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        $fieldsToExclude = ['create_time', 'update_time'];

        // 1. 查询该项目的所有标题
        $list = Db::table('panel_transition_scene')
            ->where('project_id', $project_id)
            ->withoutField($fieldsToExclude)
            ->select()
            ->toArray();
        if ($scale != 100) {
            $scaleRatio = $scale / 100;
            foreach ($list as &$item) {
                $item['width'] = (int) ($item['width'] * $scaleRatio);
                $item['height'] = (int) ($item['height'] * $scaleRatio);
                $item['image_path'] = $item['image_path'] . '?scale=' . $scale;
            }
        }

        // 3. 返回标题数据
        return success($list, empty($list) ? '转场配置不存在，请检查是否上传' : '转场获取成功');
    }
}
