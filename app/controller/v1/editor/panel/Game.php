<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;
use app\model\Panel\game\PanelGame;
use app\model\Panel\game\PanelGameLocalizationText;

class Game
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $projectId = Request::param('project_id');


        $gameData = PanelGame::where('project_id', $projectId)->select();

        // 如果没有数据误
        if (!$gameData || $gameData->isEmpty()) {
            // 假设你要检查项目中是否存在图片
            $image = Image::where('projectId', $projectId)->find();
            if (!$image) {
                return error('图片资源为空，先配置图片资源');
            }
            $param['project_id'] = $projectId;
            $createResult = $this->createGame($param);

            if ($createResult) {
                // 重新查询新创建的数据
                $gameData = PanelGame::with('localizationText')
                    ->where('project_id', $projectId)
                    ->select();

                return success($gameData, '该项目尚未配置，已自动创建默认配置.');
            } else {
                return error('该项目尚未配置，自动创建默认配置失败.');
            }
        }

        // 获取本地化文本
        $gameData = PanelGame::with('localizationText')
            ->where('project_id', $projectId)
            ->select();

        return success($gameData);
    }

    /**
     * 保存游戏数据（创建或更新）
     */
    public function save()
    {
        $param = Request::post();

        if (empty($param['project_id'])) {
            return error('关联项目ID 不能为空.');
        }

        try {
            Db::startTrans();

            if (!empty($param['id'])) {
                // 更新操作
                $result = $this->updateGame($param);
            } else {
                // 创建操作
                $result = $this->createGame($param);
            }

            Db::commit();

            if ($result) {
                $message = !empty($param['id']) ? '更新成功' : '创建成功';
                return success($result, $message);
            } else {
                return error('操作失败', 500);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return error('操作失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新游戏数据及对应的本地化文本
     */
    private function updateGame(array $param)
    {
        $game = PanelGame::find($param['id']);
        if (!$game) {
            return error('记录不存在');
        }

        $param['update_time'] = date('Y-m-d H:i:s');
        $gameSaveResult = $game->save($param);

        if ($gameSaveResult) {
            // 更新或创建对应的本地化文本
            $this->updateOrCreateLocalizationText($param);
        }

        return $gameSaveResult;
    }

    /**
     * 更新或创建本地化文本
     */
    private function updateOrCreateLocalizationText($param)
    {
        $localizationText = PanelGameLocalizationText::where('id', $param['localizationText']['id'])->find();

        // 更新现有记录
        $updateData = $param['localizationText'];
        $updateData['update_time'] = date('Y-m-d H:i:s');

        return $localizationText->save($updateData);
    }

    /**
     * 创建游戏数据及对应的本地化文本
     */
    private function createGame(array $param)
    {
        // 移除id字段，确保自增
        unset($param['id']);

        // 设置创建和更新时间
        $currentTime = date('Y-m-d H:i:s');
        $baseGameParam = [
            'project_id' => $param['project_id'],
            'create_time' => $currentTime,
            'update_time' => $currentTime,
        ];

        // 根据原代码逻辑，创建两条记录
        $games = [];

        // 第一条记录：type为hint
        $game1 = new PanelGame;
        $game1->data(array_merge($baseGameParam, [
            'y' => '800',
            'type' => 'hint'
        ]));
        $game1->save();

        // 创建对应的本地化文本
        $this->createLocalizationText($game1->id);
        $games[] = $game1;

        // 第二条记录：type为pause  
        $game2 = new PanelGame;
        $game2->data(array_merge($baseGameParam, [
            'y' => '650',
            'type' => 'pause'
        ]));
        $game2->save();

        // 创建对应的本地化文本
        $this->createLocalizationText($game2->id);
        $games[] = $game2;

        // 返回创建的游戏数组
        return $games;
    }

    /**
     * 创建本地化文本记录
     */
    private function createLocalizationText(int $panelGameId)
    {
        $localizationText = new PanelGameLocalizationText;
        $localizationText->data([
            'panel_game_id' => $panelGameId,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        $localizationText->save();

        return $localizationText;
    }

}
