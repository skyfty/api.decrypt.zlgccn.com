<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;

class Title
{
    /**
     * 获取指定项目的标题
     * GET /v1/editor/panel/GetTitle
     *
     * 参数：
     *   project_id  int  必选
     *   scale       int  可选，默认100，范围1-100
     */
    public function GetTitle()
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

        $fieldsToExclude = ['content', 'create_time', 'update_time'];

        // 1. 查询该项目的所有标题
        $list = Db::table('panel_title')
            ->where('project_id', $project_id)
            ->withoutField($fieldsToExclude)
            ->find();

        // 2. 如果查询结果为空，则自动创建一条默认标题
        if (empty($list)) {
            return error('标题获取失败，请确认是否配置', 500);
        } else {
            // 遍历查询结果
            $list['backgroundUrl'] = Image::getImageUrlById($list['background_id'], $scale);
            unset($list['background_id']);

            if ($scale != 100) {
                $list['width'] = $this->applyScale($list['width'], $scale);
                $list['height'] = $this->applyScale($list['height'], $scale);
            }
            // 标题数据存在
            $list_item = Db::table('panel_title_item')
                ->where('panel_title_id', $list['id'])
                ->withoutField($fieldsToExclude)
                ->order('order', 'asc')
                ->select()
                ->toArray();
            foreach ($list_item as &$item) {
                $item['backgroundUrl'] = Image::getImageUrlById($item['background_id'], $scale);
                unset($item['background_id']);
                if ($scale != 100) {
                    $item['width'] = $this->applyScale($item['width'], $scale);
                    $item['height'] = $this->applyScale($item['height'], $scale);
                    $item['x'] = $this->applyScale($item['x'], $scale);
                    $item['y'] = $this->applyScale($item['y'], $scale);
                }
            }
            $list['panel_title_items'] = $list_item;
        }

        // 3. 返回标题数据
        return success($list, empty($list) ? '标题获取失败，请确认是否配置' : '标题获取成功');
    }


    /**
     * 应用缩放变换
     */
    private function applyScale($item, $scale)
    {
        $scaleRatio = $scale / 100; // 预计算缩放比例
        return (int) ($item * $scaleRatio);
    }
    private function applyImageScale($item, $scale)
    {
        return $item . '?scale=' . $scale;
    }
}
