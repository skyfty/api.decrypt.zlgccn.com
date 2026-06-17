<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use think\facade\Db;
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

        $attributeImageId = $game['attribute_image_id'] ?? null;
        $game['attributeImageUrl'] = ($game['attribute_display_type'] ?? '') === 'image' && !empty($attributeImageId)
            ? Image::getImageUrlById($attributeImageId, $scale)
            : '';

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

    private function reorderByGroupBase(array $items): array
    {
        if (empty($items)) {
            return $items;
        }

        $groupIds = [];
        foreach ($items as $item) {
            $groupId = isset($item['button_point_group_id']) ? (int) $item['button_point_group_id'] : 0;
            if ($groupId > 0) {
                $groupIds[] = $groupId;
            }
        }

        if (empty($groupIds)) {
            usort($items, static function (array $left, array $right): int {
                $leftSort = (int) ($left['sort'] ?? 0);
                $rightSort = (int) ($right['sort'] ?? 0);
                if ($leftSort !== $rightSort) {
                    return $leftSort <=> $rightSort;
                }
                return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
            });

            foreach ($items as $index => &$item) {
                $item['sort'] = $index;
            }
            unset($item);

            return $items;
        }

        $groupSortMap = Db::name('button_point_group')
            ->whereIn('id', array_values(array_unique($groupIds)))
            ->column('sort', 'id');

        foreach ($items as &$item) {
            $groupId = isset($item['button_point_group_id']) ? (int) $item['button_point_group_id'] : 0;
            $itemSort = (int) ($item['sort'] ?? 0);
            $itemId = (int) ($item['id'] ?? 0);

            if ($groupId > 0) {
                $groupSort = isset($groupSortMap[$groupId]) ? (int) $groupSortMap[$groupId] : 0;
                $item['_major_sort'] = $groupSort;
                $item['_type_order'] = 1;
                $item['_group_id'] = $groupId;
                $item['_minor_sort'] = $itemSort;
                $item['_id_sort'] = $itemId;
            } else {
                $item['_major_sort'] = $itemSort;
                $item['_type_order'] = 0;
                $item['_group_id'] = 0;
                $item['_minor_sort'] = 0;
                $item['_id_sort'] = $itemId;
            }
        }
        unset($item);

        usort($items, static function (array $left, array $right): int {
            $leftMajor = (int) ($left['_major_sort'] ?? 0);
            $rightMajor = (int) ($right['_major_sort'] ?? 0);
            if ($leftMajor !== $rightMajor) {
                return $leftMajor <=> $rightMajor;
            }

            $leftType = (int) ($left['_type_order'] ?? 0);
            $rightType = (int) ($right['_type_order'] ?? 0);
            if ($leftType !== $rightType) {
                return $leftType <=> $rightType;
            }

            $leftGroup = (int) ($left['_group_id'] ?? 0);
            $rightGroup = (int) ($right['_group_id'] ?? 0);
            if ($leftGroup !== $rightGroup) {
                return $leftGroup <=> $rightGroup;
            }

            $leftMinor = (int) ($left['_minor_sort'] ?? 0);
            $rightMinor = (int) ($right['_minor_sort'] ?? 0);
            if ($leftMinor !== $rightMinor) {
                return $leftMinor <=> $rightMinor;
            }

            return (int) ($left['_id_sort'] ?? 0) <=> (int) ($right['_id_sort'] ?? 0);
        });

        foreach ($items as $index => &$item) {
            $item['sort'] = $index;
            unset($item['_major_sort']);
            unset($item['_type_order']);
            unset($item['_group_id']);
            unset($item['_minor_sort']);
            unset($item['_id_sort']);
        }
        unset($item);

        return $items;
    }

    private function buildGameData($projectId, int $scale): array
    {
        $gameData = PanelGame::with('localizationText')
            ->where('project_id', $projectId)
            ->select()
            ->toArray();

        foreach ($gameData as &$game) {
            $game = $this->normalizeGameItem($game, $scale);
        }
        unset($game);

        return $this->reorderByGroupBase($gameData);
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

        $gameData = $this->buildGameData($projectId, $scale);

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

        $gameData = $this->buildGameData($projectId, $scale);

        return success($gameData, empty($gameData) ? '设置获取失败，请确认是否配置' : '设置获取成功');
    }
}
