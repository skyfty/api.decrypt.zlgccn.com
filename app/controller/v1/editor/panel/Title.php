<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class Title
{
    /**
     * 获取指定项目的标题
     * GET /v1/editor/panel/GetTitle
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetTitle()
    {
        $project_id = (int) Request::param('project_id', 0);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 1. 查询该项目的所有标题
        $list = Db::table('panel_title')
            ->where('project_id', $project_id)
            ->find();

        // 2. 如果查询结果为空，则自动创建一条默认标题
        if (empty($list)) {
            $imageRecord = Db::table('image')->where('projectId', $project_id)->find();

            if (!empty($imageRecord) && is_array($imageRecord)) {
                $image_id = $imageRecord['id'];
            } else {
                return error('图片资源为空，先配置图片资源', 500);
            }

            // 构造要插入的数据
            $newTitle = [
                'project_id'     => $project_id,
                'background_id'  => $image_id,
            ];

            // 插入数据并获取自增ID（可选，如果你需要记录ID）
            $newTitleId = Db::table('panel_title')->insertGetId($newTitle);

            if ($newTitleId === false || $newTitleId <= 0) {
                return error('标题创建失败，请稍后重试', 500);
            }

            // 再次查询，获取刚刚插入的数据
            $list = Db::table('panel_title')
                ->where('project_id', $project_id)
                ->find();
        }

        // 标题数据存在
        $list_item = Db::table('panel_title_item')
            ->where('panel_title_id', $list['id'])
            ->select()
            ->toArray();
        foreach ($list_item as &$item) {
            $item['localizationText'] = Db::table('panel_title_localizationText')
                ->where('panel_title_item_id', $item['id'])->find();
            if ($item['button_type'] === 1) {
                $item['param'] = Db::table('panel_title_start')
                    ->where('panel_title_item_id', $item['id'])->find();
            }
        }

        $list['panel_title_items'] = $list_item;


        // 3. 返回标题数据
        return success($list, empty($list) ? '标题不存在已自动创建成功' : '标题获取成功ss');
    }

    /**
     * 上传/更新指定项目的标题
     * POST /v1/editor/panel/UploadTitle
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   project_id     int     必填    项目ID
     *   image_id       int     必填    图片ID
     *   background_id  int     必填    图片ID
     */
    public function updateTitle()
    {
        // 获取完整JSON数据
        $data = Request::param();
        if (empty($data['id'])) {
            return error('标题ID不能为空', 400);
        }

        // 开启事务
        Db::startTrans();
        try {
            // 1. 更新主表 panel_title
            $titleData = [
                'width'        => $data['width'] ?? 1080,
                'height'       => $data['height'] ?? 1920,
                'background_id' => $data['background_id'],
                'background_audio' => $data['background_audio'],
                'update_time'  => date('Y-m-d H:i:s')
            ];
            Db::table('panel_title')
                ->where('id', $data['id'])
                ->update($titleData);

            // 2. 处理子项 panel_title_items
            if (!empty($data['panel_title_items'])) {
                foreach ($data['panel_title_items'] as $item) {
                    $this->updateTitleItem($item);
                }
            }

            Db::commit();
            return success([], '标题更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('更新失败: ' . $e->getMessage(), 500);
        }
    }

    // 子项更新方法
    protected function updateTitleItem($item)
    {
        // 更新 panel_title_item
        $itemData = [
            'x' => $item['x'],
            'y' => $item['y'],
            'width' => $item['width'],
            'height' => $item['height'],
            'background_id' => $item['background_id'],
            'z_index' => $item['z_index'],
            'order' => $item['order'],
            'update_time' => date('Y-m-d H:i:s')
        ];
        Db::table('panel_title_item')
            ->where('id', $item['id'])
            ->update($itemData);

        // 更新 localizationText
        if (!empty($item['localizationText'])) {
            $textData = [
                'content' => $item['localizationText']['content'],
                'color' => $item['localizationText']['color'],
                'size' => $item['localizationText']['size'],
                'x' => $item['localizationText']['x'],
                'y' => $item['localizationText']['y'],
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::table('panel_title_localizationText')
                ->where('panel_title_item_id', $item['id'])
                ->update($textData);
        }

        // 特殊类型处理（如common_start）
        if ($item['button_type'] === 1 && !empty($item['param'])) {
            $paramData = [
                'city_id' => $item['param']['city_id'],
                'room_id' => $item['param']['room_id'],
                'success_audio' => $item['param']['success_audio'],
                'error_audio' => $item['param']['error_audio'],
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::table('panel_title_start')
                ->where('panel_title_item_id', $item['id'])
                ->update($paramData);
        }
    }


    /**
     * 上传/更新指定项目的标题项
     * POST /v1/editor/panel/UploadTitle
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   project_id     int     必填    项目ID
     *   image_id       int     必填    图片ID
     *   background_id  int     必填    图片ID
     */
    public function UploadTitleItem()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        $table          = 'panel_title_item';
        $id             = (int) Request::post('id', 0);
        $panel_title_id     = (int) Request::post('panel_title_id', 0);

        if (empty($panel_title_id)) {
            return error('标题ID不能为空', 400);
        }

        $row = [
            'panel_title_id'     =>  $panel_title_id,
            'width'     =>  (int) Request::post('width', 250),
            'height'     =>  (int) Request::post('height', 60),
            'x'     =>  (int) Request::post('x', 0),
            'y'     =>  (int) Request::post('y', 0),
            'z_index'     =>  (int) Request::post('z_index', 0),
            'order'     =>  (int) Request::post('order', 0),
            'button_type'     => Request::post('button_type'),
            'background_id'     =>  (int) Request::post('background_id', 0),
            'content'     => Request::post('content', '开始'),
            'multiLanguage'     =>  (int) Request::post('multiLanguage', 1),
            'color'     =>  Request::post('color', '#ff0000'),
            'size'     =>  (int) Request::post('size', 30),
            'update_time'   => date('Y-m-d H:i:s'),
        ];


        if ($id === 0) {
            $row['create_time'] = date('Y-m-d H:i:s');
            $affected = Db::table($table)->insertGetId($row);

            Db::table('panel_title_localizationText')->insert([
                'panel_title_item_id' => $affected,
                'content' => $row['content'],
                'color' => $row['color'],
                'size' => $row['size'],
                'x' => 0,
                'y' => 0,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            if ((int) $row['button_type'] === 1) {
                Db::table('panel_title_start')->insert([
                    'panel_title_item_id' => $affected,
                    'city_id' => null,
                    'room_id' => null,
                    'success_audio' => null,
                    'error_audio' => null,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }

            return success($row, '标题项创建成功');
        } else {
            // ✅ 更新
            $affected = Db::table($table)->where('id', $id)->update($row);
            if ($affected === 0) {
                return error('未找到对应记录或数据未变更', 404);
            } else {
                return success($row, '标题项更新成功');
            }
        }
    }

    public function DeleteTitleItem()
    {
        $id = (int) Request::param('id', 0);
        $titleId = (int) Request::param('panel_title_id', 0);

        if ($id <= 0 || $titleId <= 0) {
            return error('标题项ID和标题ID不能为空', 400);
        }

        $item = Db::table('panel_title_item')->where('id', $id)->find();
        if (!$item) {
            return error('标题项不存在', 404);
        }

        if ((int) $item['panel_title_id'] !== $titleId) {
            return error('标题项不属于当前标题', 400);
        }

        Db::startTrans();
        try {
            Db::table('panel_title_localizationText')->where('panel_title_item_id', $id)->delete();
            Db::table('panel_title_start')->where('panel_title_item_id', $id)->delete();
            Db::table('panel_title_item')->where('id', $id)->delete();
            Db::commit();

            return success(['id' => $id], '标题项删除成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('标题项删除失败：' . $e->getMessage(), 500);
        }
    }
}
