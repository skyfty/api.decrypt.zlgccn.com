<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use app\model\Image;
use app\model\Panel\game\PanelGame;

class Game
{
    private function defaultLocalizationText(): array
    {
        return [
            'width' => 160,
            'height' => 40,
            'x' => 0,
            'y' => 0,
            'content' => '',
            'color' => '#ffffff',
            'size' => 16,
        ];
    }

    private function normalizeGameItem(array $game, int $scale): array
    {
        $imageId = $game['image_id'] ?? null;
        $game['imageUrl'] = !empty($imageId)
            ? Image::getImageUrlById($imageId, $scale)
            : '';
        unset($game['image_id']);

        $game['localizationText'] = array_merge(
            $this->defaultLocalizationText(),
            (array) ($game['localizationText'] ?? [])
        );

        if ($scale != 100) {
            $scaleRatio = $scale / 100;
            $game['width'] = (int) ($game['width'] * $scaleRatio);
            $game['height'] = (int) ($game['height'] * $scaleRatio);
            $game['x'] = (int) ($game['x'] * $scaleRatio);
            $game['y'] = (int) ($game['y'] * $scaleRatio);
            $game['localizationText']['width'] = (int) ($game['localizationText']['width'] * $scaleRatio);
            $game['localizationText']['height'] = (int) ($game['localizationText']['height'] * $scaleRatio);
            $game['localizationText']['x'] = (int) ($game['localizationText']['x'] * $scaleRatio);
            $game['localizationText']['y'] = (int) ($game['localizationText']['y'] * $scaleRatio);
            $game['localizationText']['size'] = (int) ($game['localizationText']['size'] * $scaleRatio);
        }

        return $game;
    }

    /**
     * 获取数据列表
     */
    public function index()
    {
        $projectId = Request::param('project_id');
        $scale = (int) Request::param('scale', 100);
        if ( empty($projectId) ) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        // 获取本地化文本
        $gameData = PanelGame::with('localizationText')
            ->where('project_id', $projectId)
            ->select();
        $gameData = $gameData->toArray();
        foreach($gameData as &$game){
            $game = $this->normalizeGameItem($game, $scale);
        }

        return success($gameData, empty($gameData) ? '设置获取失败，请确认是否配置' : '设置获取成功');
    }
    /**
     * 获取指定项目的设置
     * GET /v1/editor/panel/GetGame
     *
     * 参数：
     *   project_id  int  必选
     *   scale       int  可选，默认100，范围1-100
     */
    public function GetGame()
    {
        $projectId = Request::param('project_id');
        $scale = (int) Request::param('scale', 100);
        if ( empty($projectId) ) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        // 获取本地化文本
        $gameData = PanelGame::with('localizationText')
            ->where('project_id', $projectId)
            ->select();
        $gameData = $gameData->toArray();
        foreach($gameData as &$game){
            $game = $this->normalizeGameItem($game, $scale);
        }

        return success($gameData, empty($gameData) ? '设置获取失败，请确认是否配置' : '设置获取成功');
    }
}
