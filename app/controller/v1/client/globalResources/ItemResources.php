<?php

declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;
use app\model\globalConfig\Item;
use app\model\Image;

class ItemResources
{
    /**
     * 获取当前项目的图片资源列表(停止维护)
     * GET /api/v1/GlobalResources/Resources
     */
    public function GetItemResources()
    {
        // 从请求参数中获取 projectId，必传
        $projectId = (int)Request::param('project_id', 0);
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
            ->field(['id', 'name', 'imageId'])
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $imageId = (int)$item['imageId'];

            // 查询图片信息
            $imageInfo = Db::table('image')
                ->where('id', $imageId)
                ->field('file') // 假定这是图片路径或 URL
                ->find();

            if ($imageInfo && isset($imageInfo['file'])) {
                $item['file'] = $imageInfo['file']; // 新增字段
            } else {
                $item['file'] = ''; // 或 null
            }
            unset($item['imageId']);
        }
        unset($item);

        return success($list, '物品资源获取成功');
    }

    public function index()
    {
        $params = request()->param();

        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }
        $itemData = Item::where('projectId', $params['project_id'])->select();
        if (!$itemData) {
            return error('Item not found', 404);
        }
        $formatData = [];
        foreach ($itemData as &$item) {
            $formatData[] = [
                'id'       => $item->id,
                'name'     => $item->name,
                'imageUrl' => Image::getImageUrlById($item->imageId, $params['scale'] ?? 100),
            ];
        }
        return success($formatData);
    }

    public function read($id)
    {
        $params = request()->param();

        $item = Item::find($id);
        if (!$item) {
            return error('Item not found', 404);
        }
        $item['imageUrl'] = Image::getImageUrlById($item['imageId'], $params['scale'] ?? 100);
        unset($item['imageId']);
        return success($item);
    }
}
