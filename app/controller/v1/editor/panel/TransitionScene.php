<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

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
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 1. 查询该项目的所有标题
        $list = Db::table('panel_transition_scene')
            ->where('project_id', $project_id)
            ->select()
            ->toArray();

        // 3. 返回标题数据
        return success($list, empty($list) ? '转场配置不存在，请检查是否上传' : '转场获取成功');
    }

    /**
     * 上传/更新指定项目的转场
     * POST /v1/editor/panel/UploadTransitionScene
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   project_id     int     必填    项目ID
     *   image_id       int     必填    图片ID
     *   background_id  int     必填    图片ID
     */
    public function UploadTransitionScene()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id             = (int) Request::post('id', 0);
        $project_id     = (int) Request::post('project_id', 0);
        $position       = Request::post('position', 'top');

        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }
        if (empty($position)) {
            return error('位置不能为空', 400);
        }

        $file = Request::file('image_path');
        if (!$file || !$file->isValid()) {
            return error('未上传宣传图片或上传失败', 400);
        }

        $row = [
            'project_id'    => $project_id,
            'position'      => $position,
            'update_time'   => date('Y-m-d H:i:s'),
            'create_time'   => date('Y-m-d H:i:s'),
        ];

        $saveDir = "resource/TransitionScene";
        $saveName = Filesystem::disk('public')->putFile($saveDir, $file);
        $image_path = 'storage/' . ltrim($saveName, '/');

        $row['image_path'] = $image_path;

        // ✅ 新增材料到数据库
        $row['id'] = Db::table('panel_transition_scene')->insertGetId($row);

        return success($row, $id > 0 ? '更新成功' : '上传成功.');
    }

    /**
     * 删除转场
     * POST /api/v1/editor/deleteAnimationFrame
     * 表单参数：
     *   id int 必须，转场图片ID
     */
    public function DeleteTransitionScene()
    {
        $id = (int) Request::get('id');

        if (empty($id)) {
            return error('转场ID必须为有效正整数', 400);
        }

        $table = 'panel_transition_scene';
        $frame = Db::name($table)->find($id);

        if (!$frame) {
            return error('转场图片不存在', 404);
        }

        // 获取图片路径字段 
        $frameImage = $frame['image_path'] ?? null;

        // 1. 先删除数据库记录 
        Db::name($table)->delete($id); 

        // 2. 如果有图片路径，尝试删除磁盘上的文件 
        if (!empty($frameImage)) {
            try {
                // 去掉开头的 'storage/' 前缀
                $pathToDelete = str_starts_with($frameImage, 'storage/')
                    ? substr($frameImage, strlen('storage/'))
                    : $frameImage;

                \think\facade\Filesystem::disk('public')->delete($pathToDelete);
            } catch (\Exception $e) {
                \think\facade\Log::error('删除转场图片失败: ' . $e->getMessage());
            }
        }

        return success(null, '转场删除成功');
    }
}
