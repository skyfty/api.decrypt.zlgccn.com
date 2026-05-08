<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use app\model\Panel\chapter\PanelChapter;

class Chapter
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
        $ChapterData = PanelChapter::with('localizationText')
            ->where('project_id', $projectId)
            ->find();
        if ($scale != 100) {
            $scaleRatio = $scale / 100;
            $ChapterData['width'] = (int) ($ChapterData['width'] * $scaleRatio);
            $ChapterData['height'] = (int) ($ChapterData['height'] * $scaleRatio);
            $ChapterData['x'] = (int) ($ChapterData['x'] * $scaleRatio);
            $ChapterData['y'] = (int) ($ChapterData['y'] * $scaleRatio);
            $ChapterData['localizationText']['width'] = (int) ($ChapterData['localizationText']['width'] * $scaleRatio);
            $ChapterData['localizationText']['height'] = (int) ($ChapterData['localizationText']['height'] * $scaleRatio);
            $ChapterData['localizationText']['x'] = (int) ($ChapterData['localizationText']['x'] * $scaleRatio);
            $ChapterData['localizationText']['y'] = (int) ($ChapterData['localizationText']['y'] * $scaleRatio);
            $ChapterData['localizationText']['size'] = (int) ($ChapterData['localizationText']['size'] * $scaleRatio);
        }

        return success($ChapterData, !$ChapterData || $ChapterData->isEmpty() ? '获取UI失败，请确认是否配置' : '获取UI成功');
    }
}
