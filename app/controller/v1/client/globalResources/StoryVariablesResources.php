<?php

declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

use think\facade\Request;
use think\facade\Db;

class StoryVariablesResources
{
    /**
     * 获取当前项目的资源列表
     * GET /api/v1/GlobalResources/Resources
     */
    public function GetStoryVariables()
    {
        $project_id = (int)Request::param('project_id', 0);

        if ($project_id <= 0) {
            return error('项目ID不能为空或无效', 400);
        }

        // 查询该 project_id 下的所有变量
        $list = Db::name('project_story_variables')
            ->where('project_id', $project_id)
            ->withoutField(['project_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        return success($list, '剧情变量获取成功');
    }



}
