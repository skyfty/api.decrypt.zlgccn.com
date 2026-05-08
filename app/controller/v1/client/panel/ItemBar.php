<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use app\model\Image;
use think\facade\Request;
use app\model\Panel\itemBar\PanelItemBar;

class ItemBar
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $projectId = Request::param('project_id');
        $scale = (int) Request::param('scale', 100);
        if (empty($projectId)) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        // 获取本地化文本
        $ItemBarData = PanelItemBar::with('ItemBarItemSlot')
            ->where('project_id', $projectId)
            ->find();
            $ItemBarData['background_image'] = Image::getImageUrlById($ItemBarData['background_image'], $scale);
            $ItemBarData['ItemBarItemSlot']['background_image'] = Image::getImageUrlById($ItemBarData['ItemBarItemSlot']['background_image'], $scale);
        if ($scale != 100) {
            $scaleRatio = $scale / 100;
            $ItemBarData['width'] = (int) ($ItemBarData['width'] * $scaleRatio);
            $ItemBarData['height'] = (int) ($ItemBarData['height'] * $scaleRatio);
            $ItemBarData['x'] = (int) ($ItemBarData['x'] * $scaleRatio);
            $ItemBarData['y'] = (int) ($ItemBarData['y'] * $scaleRatio);
            $ItemBarData['ItemBarItemSlot']['width'] = (int) ($ItemBarData['ItemBarItemSlot']['width'] * $scaleRatio);
            $ItemBarData['ItemBarItemSlot']['height'] = (int) ($ItemBarData['ItemBarItemSlot']['height'] * $scaleRatio);
            unset($ItemBarData['ItemBarItemSlot']['x']);
            unset($ItemBarData['ItemBarItemSlot']['y']);
        }

        return success($ItemBarData, !$ItemBarData || $ItemBarData->isEmpty() ? '获取UI失败，请确认是否配置' : '获取UI成功');
    }
}
