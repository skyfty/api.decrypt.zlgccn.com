<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use app\model\Image;
use app\model\Panel\game\PanelGame;

class Game
{
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
        foreach($gameData as &$game){
            $game['imageUrl'] = Image::getImageUrlById($game['image_id'], $scale);
            unset($game['image_id']);
            if ($scale != 100) {
                $scaleRatio = $scale / 100;
                $game['width'] = (int) ($game['width'] * $scaleRatio);
                $game['height'] = (int) ($game['height'] * $scaleRatio);
                $game['x'] = (int) ($game['x'] * $scaleRatio);
                $game['y'] = (int) ($game['y'] * $scaleRatio);
            }
        }

        return success($gameData, !$gameData || $gameData->isEmpty() ? '设置获取失败，请确认是否配置' : '设置获取成功');
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
        foreach($gameData as &$game){
            $game['imageUrl'] = Image::getImageUrlById($game['image_id'], $scale);
            unset($game['image_id']);
            if ($scale != 100) {
                $scaleRatio = $scale / 100;
                $game['width'] = (int) ($game['width'] * $scaleRatio);
                $game['height'] = (int) ($game['height'] * $scaleRatio);
                $game['x'] = (int) ($game['x'] * $scaleRatio);
                $game['y'] = (int) ($game['y'] * $scaleRatio);
            }
        }

        return success($gameData, !$gameData || $gameData->isEmpty() ? '设置获取失败，请确认是否配置' : '设置获取成功');
    }
}
