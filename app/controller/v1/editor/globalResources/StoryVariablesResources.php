<?php

declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

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
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $project_id = (int)Request::param('project_id', 0);

        if ($project_id <= 0) {
            return error('项目ID不能为空或无效', 400);
        }

        // 查询该 project_id 下的所有变量
        $list = Db::name('project_story_variables')
            ->where('project_id', $project_id)
            ->select()
            ->toArray();

        return success($list, '剧情变量获取成功');
    }

    /**
     * 新增或更新剧情变量
     * POST /api/v1/GlobalResources/SaveStoryVariable
     * 参数格式支持 JSON 或 Form-Data
     */
    public function SaveStoryVariable()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 获取并验证参数
        $params = Request::post(); // 默认获取所有传入参数（包括 JSON body，前提是你配置了 body 解析）

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $project_id = (int)$params['project_id'] ?? 0;
        $variable_key = trim((string)($params['variable_key'] ?? ''));
        // $operators   = (int) Request::post('operators', 0);
        $variable_value = $params['variable_value'] ?? null;

        // 必填字段校验
        if ($project_id <= 0) {
            return error('项目ID不能为空或无效', 400);
        }
        if (empty($variable_key)) {
            return error('变量键名不能为空', 400);
        }
        if ($variable_value === null || $variable_value === '') {
            return error('变量值不能为空', 400);
        }


        // 构造要保存的数据（不含 id）
        $data = [
            'project_id' => $project_id,
            'variable_key' => $variable_key,
            // 'operators' => $operators,
            'variable_value' => $variable_value,
            // 可选：增加更新时间
            'update_time' => date('Y-m-d H:i:s'),
        ];

        try {
            if ($id > 0) {
                // 更新：根据 id 查找并更新记录
                $exist = Db::name('project_story_variables')->where('id', $id)->find();
                if (!$exist) {
                    return error('要更新的剧情变量不存在', 404);
                }

                // 可选：检查 project_id 是否被修改，避免越权
                if ((int)$exist['project_id'] !== $project_id) {
                    return error('不允许修改项目ID', 403);
                }

                $result = Db::name('project_story_variables')
                    ->where('id', $id)
                    ->update($data);

                if ($result === false) {
                    return error('更新失败', 500);
                }

                return success(['id' => $id], '剧情变量更新成功');
            } else {
                // 插入：新增一条记录
                $data['create_time'] = date('Y-m-d H:i:s');
                $id = Db::name('project_story_variables')->insertGetId($data);

                if (!$id) {
                    return error('新增失败', 500);
                }

                return success(['id' => $id], '剧情变量新增成功');
            }
        } catch (\Exception $e) {
            // 捕获异常，比如唯一键冲突等
            return error('操作失败：' . $e->getMessage(), 500);
        }
    }


}
