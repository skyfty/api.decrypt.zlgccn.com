<?php

declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class Resources
{
    /**
     * 上传文件并入库
     * POST /api/v1/GlobalResources/UploadResource
     *
     * 表单字段：
     *   id          int     可选  传 0 或留空=新增；传已有 id=更新
     *   name        string  可选  不传则用原文件名
     *   file        file    必选  二进制文件
     *   type        string  必选  image|audio|video|animation|model
     *   projectId   string  必选  项目ID
     */
    public function UploadResource()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 1. 基本校验
        $id        = (int) Request::post('id', 0);
        $type      = strtolower(trim(Request::post('type', '')));
        $projectId = trim(Request::post('projectId', ''));
        $whiteList = ['image', 'audio', 'video', 'animation', 'model'];

        if (!in_array($type, $whiteList, true)) {
            return error('类型不合法', 400);
        }

        if (empty($projectId)) {
            return error('项目ID不能为空', 400);
        }

        // 2. 表存在校验
        if (empty(Db::query("SHOW TABLES LIKE '{$type}'"))) {
            return error('资源类型不存在', 404);
        }

        // 3. 文件处理（可选）
        $file = Request::file('file');
        $accessUrl = null;

        if ($file && $file->isValid()) {
            // 有上传文件时处理文件
            $saveDir = "resource/{$type}";
            $saveName = Filesystem::disk('public')
                ->putFile($saveDir, $file);
            $accessUrl = 'storage/' . ltrim($saveName, '/');
        }

        // 4. 组装数据
        $row = [
            'projectId' => $projectId,
            'name'      => Request::post('name', $file ? $file->getOriginalName() : ''),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if($type === 'image') {
            $row['category_id'] = Request::post('category_id', 0);
            $row['image_type'] = Request::post('image_type', 'png');
        }

        // 如果有新上传的文件，更新文件路径
        if ($accessUrl) {
            $row['file'] = $accessUrl;
        }

        // 5. 新增 or 更新
        if ($id > 0) {
            // 更新操作
            $exists = Db::name($type)->where(['id' => $id, 'projectId' => $projectId])->find();
            if (!$exists) {
                return error('资源不存在或无权限', 404);
            }

            // 如果是新增记录且有文件，或者更新记录时有新文件，才需要文件
            if ($id === 0 && !$accessUrl) {
                return error('新增资源必须上传文件', 400);
            }

            Db::name($type)->where('id', $id)->update($row);
            $row['id'] = $id;
        } else {
            // 新增操作必须要有文件
            if (!$accessUrl) {
                return error('新增资源必须上传文件', 400);
            }

            $row['create_time'] = date('Y-m-d H:i:s');
            $row['id'] = Db::name($type)->insertGetId($row);
        }

        return success($row, $id > 0 ? '更新成功' : '添加成功');
    }
    /**
     * 获取项目资源列表
     * GET /api/v1/GlobalResources/Resources
     *
     * 查询参数：
     *   type        string  必选  image|audio|video|animation|model
     *   projectId   string  必选  项目ID
     */
    public function GetResources()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 取 GET 参数
        $type = Request::param('type', '');
        $projectId = Request::param('projectId', '');

        if ($type === '') {
            return error('类型不能为空', 400);
        }

        if ($projectId === '') {
            return error('项目ID不能为空', 400);
        }

        // 白名单校验
        $allowTypes = ['image', 'audio', 'video', 'animation', 'model'];
        if (!in_array($type, $allowTypes, true)) {
            return error('类型不合法', 400);
        }

        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$type}'");

        if (empty($exists)) {
            return error("资源类型不存在", 404);
        }

        $list = Db::name($type)
            ->where('projectId', $projectId)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return success($list, $type . '资源获取成功');
    }
}
