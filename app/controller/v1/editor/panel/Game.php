<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;
use app\model\Image;
use app\model\Panel\game\PanelGame;
use app\model\Panel\game\PanelGameLocalizationText;

class Game
{
    private function toInt($value, int $default = 0): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function toString($value, string $default = ''): string
    {
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return $default;
    }

    private function findProjectDefaultImageId(int $projectId): int
    {
        if ($projectId <= 0) {
            return 0;
        }

        return (int) Image::where('projectId', $projectId)->value('id');
    }

    private function assertImageExists(int $projectId, int $imageId): void
    {
        if ($imageId <= 0) {
            throw new \InvalidArgumentException('图片ID不能为空');
        }

        $imageExists = Image::where('id', $imageId)
            ->where('projectId', $projectId)
            ->find();

        if (! $imageExists) {
            throw new \InvalidArgumentException('图片ID不存在或不属于当前项目');
        }
    }

    private function defaultLocalizationTextPayload(): array
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

    private function buildGamePayload(array $param, bool $bootstrap = false, bool $isUpdate = false): array
    {
        $currentTime = date('Y-m-d H:i:s');

        $payload = [
            'project_id' => $this->toInt($param['project_id'] ?? 0),
            'image_id' => $this->toInt($param['image_id'] ?? 0),
            'attribute_id' => $this->toInt($param['attribute_id'] ?? 0),
            'attribute_name' => $this->toString($param['attribute_name'] ?? ''),
            'sort' => $this->toInt($param['sort'] ?? 0),
            'name' => $this->toString($param['name'] ?? 'newGame', 'newGame'),
            'width' => $this->toInt($param['width'] ?? 200, 200),
            'height' => $this->toInt($param['height'] ?? 200, 200),
            'x' => $this->toInt($param['x'] ?? 0),
            'y' => $this->toInt($param['y'] ?? 0),
            'anchors' => $this->toInt($param['anchors'] ?? 5, 5),
            'wxSafeArea' => $this->toInt($param['wxSafeArea'] ?? 0),
            'visible' => array_key_exists('visible', $param) ? $this->toInt($param['visible'], 1) : 1,
            'frozen' => array_key_exists('frozen', $param) ? $this->toInt($param['frozen'], 0) : 0,
            'multiLanguage' => $this->toInt($param['multiLanguage'] ?? 0),
            'status' => $this->toInt($param['status'] ?? 0),
            'resource_type' => $this->toInt($param['resource_type'] ?? 0),
            'sub_resource_type' => $this->toInt($param['sub_resource_type'] ?? 0),
            'resource_id' => $this->toInt($param['resource_id'] ?? 0),
            'type' => $this->toString($param['type'] ?? 'hint', 'hint'),
            'update_time' => $currentTime,
        ];

        if (! $isUpdate) {
            $payload['create_time'] = $currentTime;
        }

        if ($bootstrap) {
            $payload['visible'] = 1;
            $payload['frozen'] = 0;
        }

        return $payload;
    }

    private function filterGamePayload(array $payload): array
    {
        $game = new PanelGame;
        $fields = array_fill_keys($game->getFields(), true);

        return array_filter(
            array_intersect_key($payload, $fields),
            static fn ($value) => is_scalar($value) || $value === null
        );
    }

    private function createLocalizationText(int $panelGameId, array $payload = []): void
    {
        $localizationText = new PanelGameLocalizationText;
        $localizationText->data(array_merge([
            'panel_game_id' => $panelGameId,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ], $this->defaultLocalizationTextPayload(), $payload));
        $localizationText->save();
    }

    private function createGameRecord(array $param, bool $bootstrap = false)
    {
        $game = new PanelGame;
        $payload = $this->filterGamePayload($this->buildGamePayload($param, $bootstrap));
        $game->allowField(array_keys($payload))->save($payload);

        $this->createLocalizationText((int) $game->id, (array) ($param['localizationText'] ?? []));

        return PanelGame::with('localizationText')->find($game->id);
    }

    private function createBootstrapGames(int $projectId)
    {
        $defaultImageId = $this->findProjectDefaultImageId($projectId);
        if ($defaultImageId <= 0) {
            throw new \RuntimeException('图片资源为空，先配置图片资源');
        }

        return [
            $this->createGameRecord([
                'project_id' => $projectId,
                'image_id' => $defaultImageId,
                'y' => 800,
                'type' => 'hint',
            ], true),
            $this->createGameRecord([
                'project_id' => $projectId,
                'image_id' => $defaultImageId,
                'y' => 650,
                'type' => 'pause',
            ], true),
        ];
    }

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
            $createResult = $this->createBootstrapGames((int) $projectId);

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
            $isAttributeType = ($param['type'] ?? '') === 'attribute';

            if (!empty($param['id'])) {
                $game = PanelGame::find((int) $param['id']);
                if (! $game) {
                    throw new \RuntimeException('记录不存在');
                }

                if (! $isAttributeType && (! array_key_exists('image_id', $param) || (int) $param['image_id'] <= 0)) {
                    $param['image_id'] = (int) $game['image_id'];
                }
            } elseif (! $isAttributeType && (! array_key_exists('image_id', $param) || (int) $param['image_id'] <= 0)) {
                $param['image_id'] = $this->findProjectDefaultImageId((int) $param['project_id']);
            }

            if (! $isAttributeType) {
                $this->assertImageExists((int) $param['project_id'], (int) $param['image_id']);
            }

            Db::startTrans();

            if (!empty($param['id'])) { 
                // 更新操作
                $result = $this->updateGame($param);
            } else {          
                // 创建单条空项目
                $result = $this->createGameRecord($param);
            }
    return error('eeeeeeeeeeeeeeeeeeee', 500);

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
            throw new \RuntimeException('记录不存在');
        }

        $param['update_time'] = date('Y-m-d H:i:s');
        $payload = $this->filterGamePayload($this->buildGamePayload($param, false, true));
        $gameSaveResult = $game->allowField(array_keys($payload))->save($payload);

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
        if (empty($param['localizationText'])) {
            return null;
        }

        $localizationTextData = array_merge(
            $this->defaultLocalizationTextPayload(),
            (array) $param['localizationText']
        );

        $localizationTextId = (int) ($localizationTextData['id'] ?? 0);
        unset($localizationTextData['id']);

        $localizationText = $localizationTextId > 0
            ? PanelGameLocalizationText::where('id', $localizationTextId)->find()
            : null;

        if (!$localizationText) {
            $this->createLocalizationText((int) $param['id'], $localizationTextData);
            return true;
        }

        // 更新现有记录
        $updateData = $localizationTextData;
        $updateData['update_time'] = date('Y-m-d H:i:s');
        $updateData['panel_game_id'] = (int) $param['id'];

        return $localizationText->save($updateData);
    }

    public function delete()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'id' => 'require|number',
            'project_id' => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $gameId = (int) $params['id'];
        $projectId = (int) $params['project_id'];
        $game = PanelGame::find($gameId);
        if (!$game) {
            return error('记录不存在', 404);
        }

        if ((int) $game['project_id'] !== $projectId) {
            return error('记录不属于当前项目', 400);
        }

        Db::startTrans();
        try {
            PanelGameLocalizationText::where('panel_game_id', $gameId)->delete();
            PanelGame::destroy($gameId);
            Db::commit();

            return success(['id' => $gameId], '删除成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('删除失败：' . $e->getMessage(), 500);
        }
    }


}
