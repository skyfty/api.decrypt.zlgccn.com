<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use app\model\Image;
use think\facade\Validate;
use app\model\Panel\attribute\PanelAttribute;

class Attribute
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        $scale = (int) Request::param('scale', 100);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        // 获取所有与项目相关的属性数据
        $attributeData = PanelAttribute::where('project_id',  $params['project_id'])->select();
        $scaleRatio = $scale / 100;
        // 对获取的数据根据group_id进行分组
        $groupedData = [];
        foreach ($attributeData as  $item) {
            if (!isset($groupedData[$item['group_id']])) {
                $groupedData[$item['group_id']] = [
                    "icon" => null,
                    "label" => null,
                    "value" => null
                ];
            }

            $defaultData = [
                "id" => $item['id'],
                "project_id" => $item['project_id'],
                "width" => $item['width'] * $scaleRatio,
                "height" => $item['height'] * $scaleRatio,
                "x" => $item['x'] * $scaleRatio,
                "y" => $item['y'] * $scaleRatio,
            ];

            // 根据type设置相应的字段
            switch ($item['type']) {
                case 'icon':
                    $groupedData[$item['group_id']]['icon'] = array_merge($defaultData, [
                        "imageUrl" => Image::getImageUrlById($item['image'], $scale)
                    ]);
                    break;
                case 'label':
                    $groupedData[$item['group_id']]['label'] = array_merge($defaultData, [
                        "color" =>  $item['color'],
                        "size" =>  $item['size'] * $scaleRatio,
                        "attribute_id" =>  $item['content'] ?? 0,
                        "backgroundUrl" => Image::getImageUrlById($item['image'], $scale)
                    ]);
                    break;
                case 'value':
                    $groupedData[$item['group_id']]['value'] = array_merge($defaultData, [
                        "color" =>  $item['color'],
                        "size" =>  $item['size'] * $scaleRatio,
                        "attribute_id" =>  $item['content'],
                        "backgroundUrl" => Image::getImageUrlById($item['image'], $scale)
                    ]);
                    break;
            }
        }

        // 准备最终返回的数据格式
        $formattedData = [];
        foreach ($groupedData as  $data) {
            $formattedData[] =  $data;
        }

        return success($formattedData);
    }
}
