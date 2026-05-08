<?php
declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class ItemResources
{
    /**
     * 获取当前项目的图片资源列表
     * GET /api/v1/GlobalResources/Resources
     */
    public function GetItemResources()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 从请求参数中获取 projectId，必传
        $projectId = (int)Request::param('projectId', 0);
        if ($projectId <= 0) {
            return error('项目ID不能为空或无效', 400);
        }

        // 白名单校验：资源类型
        $type = Request::param('type', '');
        if ($type === '') {
            return error('类型不能为空', 400);
        }

        // 白名单：只允许 'item' 类型
        $allowTypes = ['item'];
        if (!in_array($type, $allowTypes, true)) {
            return error('类型不合法', 400);
        }

        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$type}'");
        if (empty($exists)) {
            return error("物品资源类型不存在", 404);
        }

        // 查询该 projectId 下的资源列表
        $list = Db::name($type)
            ->where('projectId', $projectId)
            ->select()
            ->toArray();

        return success($list, '物品资源获取成功');
    }

    /**
     * 更新或新增物品资源
     * POST /api/v1/GlobalResources/UploadItemResources
     *
     * 表单/JSON 参数：
     *   id        int     可选，0 或空=新增；否则为更新
     *   name      string  可选，不传则用默认名
     *   imageId   string  必传，图片资源ID
     *   projectId int     必传，资源所属项目ID
     */
    public function UploadItemResources()
    {
        $type = 'item'; // 资源类型固定为 item

        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$type}'");
        if (empty($exists)) {
            return error("物品资源类型不存在", 404);
        }

        // 必传参数
        $projectId = (int)Request::post('projectId', 0);
        $id = (int)Request::post('id', 0);
        $name = Request::post('name', '');
        $imageId = Request::post('imageId', '');

        if ($projectId <= 0) {
            return error('项目ID不能为空或无效', 400);
        }

        if (empty($imageId)) {
            return error('图片资源ID不能为空', 400);
        }

        $originName = $name ?: '未命名资源';

        $data = [
            'projectId' => $projectId,  // ✅ 使用 projectId 而非 user_id
            'name' => $originName,
            'imageId' => $imageId,
            'update_time'=> date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 更新：确保只更新当前项目的资源，防止越权
            $result = Db::name($type)
                ->where('id', $id)
                ->where('projectId', $projectId)  // 重要：只允许更新属于该项目的资源
                ->update($data);

            if ($result === false) {
                return error('物品更新失败', 500);
            }
            if ($result === 0) {
                return error('未找到要更新的物品资源，或不属于该项目', 404);
            }

            return success(['id' => $id], '物品更新成功');
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            // 新增
            $newId = Db::name($type)->insertGetId($data);
            if (!$newId) {
                return error('物品添加失败', 500);
            }
            return success(['id' => $newId], '物品添加成功');
        }
    }
}