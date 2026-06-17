<?php

declare(strict_types=1);

namespace app\controller\v1\client\project_task;

use app\BaseController;
use think\facade\Request;
use app\model\Project;
use app\model\Image;
use app\model\Audio;
use app\model\HintPoint\HintPoint;
use app\model\AnimationAction;
use app\model\PanelTitle;
use app\model\Trans\TranslationKeyword;
use app\model\StoryPoint\StoryPoint;

class ProjectController extends BaseController
{
    private $project_id = 0;
    private $city_id = 0;
    private $room_id = 0;
    private $isHome = true;
    private $scale = 100;

    private function reorderButtonPointsByGroupBase(array $items): array
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

        $groupSortMap = [];
        if (!empty($groupIds)) {
            $groupSortMap = \think\facade\Db::name('button_point_group')
                ->whereIn('id', array_values(array_unique($groupIds)))
                ->column('sort', 'id');
        }

        $decorated = [];
        foreach ($items as $item) {
            $groupId = isset($item['button_point_group_id']) ? (int) $item['button_point_group_id'] : 0;
            $itemSort = (int) ($item['sort'] ?? 0);
            $itemId = (int) ($item['id'] ?? 0);

            if ($groupId > 0) {
                $groupSort = isset($groupSortMap[$groupId]) ? (int) $groupSortMap[$groupId] : 0;
                $decorated[] = [
                    'item' => $item,
                    'major' => $groupSort,
                    'type_order' => 1,
                    'group_id' => $groupId,
                    'minor' => $itemSort,
                    'id_sort' => $itemId,
                ];
            } else {
                $decorated[] = [
                    'item' => $item,
                    'major' => $itemSort,
                    'type_order' => 0,
                    'group_id' => 0,
                    'minor' => 0,
                    'id_sort' => $itemId,
                ];
            }
        }

        usort($decorated, static function (array $left, array $right): int {
            if ($left['major'] !== $right['major']) {
                return $left['major'] <=> $right['major'];
            }

            if ($left['type_order'] !== $right['type_order']) {
                return $left['type_order'] <=> $right['type_order'];
            }

            if ($left['group_id'] !== $right['group_id']) {
                return $left['group_id'] <=> $right['group_id'];
            }

            if ($left['minor'] !== $right['minor']) {
                return $left['minor'] <=> $right['minor'];
            }

            return $left['id_sort'] <=> $right['id_sort'];
        });

        $result = [];
        foreach ($decorated as $index => $entry) {
            $entry['item']['sort'] = $index;
            $result[] = $entry['item'];
        }

        return $result;
    }

    public function getScaledProjectData()
    {
        $this->project_id = Request::param('project_id');

        $this->city_id = Request::param('city_id');

        $this->room_id = Request::param('room_id');

        $this->isHome = filter_var(Request::param('isHome', true), FILTER_VALIDATE_BOOLEAN);

        $this->scale = Request::param('scale', 0);

        if (!$this->project_id) {
            return error('project_id参数错误');
        }

        $query = Project::with([
            'panelTitle.buttonPointList',
            'citys',
            'citys.rooms',
            'citys.rooms.buttonPoints' => function ($query) {
                $query->with([
                    'audioResources.audio',  // 预加载音频关系
                    'param',
                    'localizationText'
                ]);
            },
            'citys.rooms.hintPoints' => function ($query) {
                $query->with(['paramRelation']);  // 预加载关联关系
            },
            'citys.rooms.storyVariablesList',
            'citys.rooms.storyPoints',
            'citys.rooms.queryBackgroundAudio'
        ]);

        // 修正：先执行查询再判断
        if ($this->project_id) {
            $query->where('id', $this->project_id);
        }

        $list = $query->select();

        // 如果没有找到项目
        if ($list->isEmpty()) {
            return error('项目不存在或已禁用');
        }

        // 格式化数据
        $formattedList = [];
        foreach ($list as $project) {
            $formattedList[] = $this->formatProject($project);
        }

        // 返回结果 如果满足$this->isHome && $this->city_id && $this->room_id，则返回success，
        // 否则将$formattedList返回JSON文件的形式

        if ($this->city_id && $this->room_id) {
            return success($formattedList, '获取项目数据成功');
        } else {
            // 以JSON文件形式返回
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="project_data.json"');
            echo json_encode($formattedList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    private function formatProject($project)
    {
        $formatted = [
            'id' => $project->id,
            'name' => $project->name . $this->isHome,
            'city_list' => []
        ];

        // 格式化城市列表（包含房间数据）
        foreach ($project->citys as $city_item) {

            // 如果传了 city_id，只处理匹配的城市
            if ($this->city_id && $city_item->id != $this->city_id) {
                continue;
            }

            $city_data = [
                'id' => $city_item->id,
                'name' => $city_item->name
            ];

            if ($this->isHome) {
                // 添加首页数据，只取第一个元素
                $panelTitle = $project->panelTitle;
                if ($panelTitle->isEmpty()) {
                    $city_data['room_list'] = [];
                } else {
                    $city_data['room_list'] = PanelTitle::formatPanelTitle([$panelTitle[0]], $this->scale);
                }
            }

            // 格式化房间列表
            foreach ($city_item->rooms as $room) {
                // 如果传了 room_id，只处理匹配的房间
                if ($this->isHome && $this->city_id && $this->room_id) {
                    continue;
                }

                if ($this->room_id && $room->id != $this->room_id) {
                    continue;
                }

                $city_data['room_list'][] = $this->buildRoomData($room);
            }

            $formatted['city_list'][] = $city_data;
        }

        return $formatted;
    }

    // 专门构建房间数据的方法
    private function buildRoomData($room)
    {
        return [
            'id' => $room->id,
            'name' => $room->name,
            'width' => $this->getScaledDimension($room->width),
            'height' => $this->getScaledDimension($room->height),
            'room_type' => $room->room_type,
            'isSave' => (bool) $room->isSave,
            'isDestroy' => (bool) $room->isDestroy,
            'imageUrl' => $this->getResourceUrl($room->imageId, 'image'),
            'backgroundAudioUrl' => $room->background_audio_file,
            'buttonPointList' => $this->queryButtonPoints($room->buttonPoints),
            'hintPointList' => $this->queryHintPoints($room->id),
            'storyVariablesList' => $room->storyVariablesList,
            'storyPointList' => $this->queryStoryPoints($room->id),
        ];
    }

    // 比例缩放
    private function getScaledDimension($dimension)
    {
        return $this->scale ? (int) round($dimension * $this->scale / 100) : $dimension;
    }

    // 统一的资源URL生成方法
    private function getResourceUrl($resourceId, $type = 'image')
    {
        if (!$resourceId) {
            return '';
        }

        $url = '';
        switch ($type) {
            case 'image':
                $url = Image::getImageUrlById($resourceId);
                break;
            case 'audio':
                $url = Audio::getAudioUrlById($resourceId);
                break;
                // 可以扩展其他资源类型
        }

        // 如果有缩放比例，添加scale参数
        return $this->scale && $type === 'image' ? $url . '?scale=' . $this->scale : $url;
    }

    // 查询按钮点数据
    private function queryButtonPoints($buttonPoints)
    {
        $processedButtonPoints = [];
        foreach ($buttonPoints as $buttonPoint) {
            // 处理音频列表，添加音频路径
            $audioList = [];
            foreach ($buttonPoint->audioResources as $audioResource) {
                $audioList[] = [
                    'id' => $audioResource->id,
                    'audio_path' => $audioResource->audio ? $audioResource->audio->file : ''
                ];
            }
            $buttonPoint['audio_list'] = $audioList;
            unset($buttonPoint->audioResources);

            // 比例缩放
            if ($this->scale) {
                $buttonPoint['width'] = $this->getScaledDimension($buttonPoint->width);
                $buttonPoint['height'] = $this->getScaledDimension($buttonPoint->height);
                $buttonPoint['x'] = $this->getScaledDimension($buttonPoint->x);
                $buttonPoint['y'] = $this->getScaledDimension($buttonPoint->y);
            }

            // 处理资源类型
            if ($buttonPoint->sub_resource_type !== 2) {
                unset($buttonPoint->animation_action);
                unset($buttonPoint->animation_play_count);
                $buttonPoint['imageUrl'] = $buttonPoint->resource_id ? $this->getResourceUrl($buttonPoint->resource_id, 'image') : '';
            } else {
                $buttonPoint['animation_action_id'] = $buttonPoint->animation_action;
                $buttonPoint['animation_action'] = $buttonPoint->animation_action ? AnimationAction::getAnimationActionById($buttonPoint->animation_action, $this->scale) : '';
            }
            if ($buttonPoint->sub_resource_type !== 3) {
                unset($buttonPoint->spine);
            }
            // 查询对应类型的数据
            $param = $this->queryButtonPointTypeData($buttonPoint);
            if ($param) {
                $buttonPoint['param'] = $param;
            }
            $buttonPoint['localizationText'] = $buttonPoint->localizationText;

            $processedButtonPoints[] = $buttonPoint;
        }

        return $this->reorderButtonPointsByGroupBase($processedButtonPoints);
    }

    // 查询提示点数据
    private function queryHintPoints($room_id)
    {
        $hintPointController = app()->make(\app\controller\v1\client\hintPoint\HintPointController::class);
        $hint_point_list = $hintPointController->index($room_id, $this->scale);
        return $hint_point_list;
    }

    // 查询故事点数据
    private function queryStoryPoints($roomId)
    {

        if (!$roomId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $list = StoryPoint::with([
            'conditions',
            'actions.assignVariable',
            'actions.operateButtonPoint',
            'actions.animationPlayMode',
            'actions.updateWeather',
            'actions.requiredConditions'
        ])->where(['room_id' => $roomId, 'status' => true])->select();

        // 创建控制器实例
        $storyPointController = app()->make(\app\controller\v1\client\storyPoint\StoryPointController::class);

        // 格式化数据
        $formattedList = [];
        foreach ($list as $storyPoint) {
            $formattedList[] = $storyPointController->formatStoryPoint($storyPoint, $this->scale);
        }

        return $formattedList;
    }

    // 查询buttonPoint_type对应的数据
    private function queryButtonPointTypeData($buttonPoint)
    {
        $typeToTableMap = [
            1 => \app\model\ButtonPointTip::class,
            2 => \app\model\ButtonPointDraggable::class,
            3 => \app\model\ButtonPointRotate::class,
            4 => \app\model\ButtonPointMove::class,
            5 => \app\model\ButtonPointNineSquarecalligraphyGrid::class,
            9 => \app\model\ButtonPointDoor::class,
            10 => \app\model\ButtonPointItem::class,
            11 => \app\model\ButtonPoint\ButtonPointChapter::class
        ];

        $modelClass = $typeToTableMap[$buttonPoint->type] ?? null;

        if (!$modelClass) {
            return null;
        }

        $data = $modelClass::where('button_point_id', $buttonPoint->id)->find();

        // 如果是 ButtonPointItem 类型，处理动态字段
        if ($buttonPoint->type === 1 && $data) {
            return $this->formatButtonPointTipData($data);
        } else if ($buttonPoint->type === 2 && $data) {
            return $this->formatButtonPointDraggableData($data);
        } else if ($buttonPoint->type === 5 && $data) {
            return $this->formatButtonPointNineSquarecalligraphyGridData($data);
        } else if ($buttonPoint->type === 9 && $data) {
            return $this->formatButtonPointDoorData($data);
        } else if ($buttonPoint->type === 10 && $data) {
            return $this->formatButtonPointItemData($data);
        }

        return $data;
    }

    private function formatButtonPointTipData($itemData)
    {
        $formatted = [
            'id' => $itemData->id,
            'button_point_id' => $itemData->button_point_id,
            'tipContent' => $itemData->tipContent,
            'tipContent_Chinese' => TranslationKeyword::queryKeyByName($itemData->tipContent),
        ];

        return $formatted;
    }

    private function formatButtonPointDoorData($itemData)
    {
        $formatted = [
            'id' => $itemData->id,
            'button_point_id' => $itemData->button_point_id,
            'isOpen' => $itemData->isOpen,
            'doorCityId' => $itemData->doorCityId,
            'doorRoomId' => $itemData->doorRoomId,
            'successVoice' => $itemData->successVoice ? Audio::getAudioUrlById($itemData->successVoice) : '', // 转换为音频路径
            'errorVoice' => $itemData->errorVoice ? Audio::getAudioUrlById($itemData->errorVoice) : '', // 转换为音频路径
            'doorType' => $itemData->doorType,
        ];

        // 基础门
        if ($itemData->doorType === 'BasicDoor') {
            $formatted['itemsID'] = $itemData->itemsID;
            $formatted['itemCount'] = $itemData->itemCount;
            $formatted['lockText'] = $itemData->lockText;
            $formatted['lockText_Chinese'] = TranslationKeyword::queryKeyByName($itemData->lockText);
            // 数字门
        } else if ($itemData->doorType === 'NumericCodeDoor') {
            $formatted['password'] = $itemData->password;
            $formatted['count'] = $itemData->count;
            $formatted['lockText'] = $itemData->lockText;
            $formatted['lockText_Chinese'] = TranslationKeyword::queryKeyByName($itemData->lockText);
            // 字母门
        } else if ($itemData->doorType === 'AlphaKeyDoor') {
            $formatted['password'] = $itemData->password;
            $formatted['count'] = $itemData->count;
            $formatted['lockText'] = $itemData->lockText;
            $formatted['lockText_Chinese'] = TranslationKeyword::queryKeyByName($itemData->lockText);
            // 拖拽门
        } else if ($itemData->doorType === 'DraggableDoor') {
            $formatted['moveOrientation'] = $itemData->moveOrientation;
            $formatted['moveDistance'] = $itemData->moveDistance;
            // 拖拽门
        } else if ($itemData->doorType === 'LogicDoor') {
            $formatted['pointAnchors'] = $itemData->pointAnchors;
            $formatted['pointX'] = $itemData->pointX;
            $formatted['pointY'] = $itemData->pointY;
        }

        return $formatted;
    }

    private function formatButtonPointItemData($itemData)
    {
        $formatted = [
            'id' => $itemData->id,
            'button_point_id' => $itemData->button_point_id,
            'itemsType' => $itemData->itemsType,
        ];

        if ($itemData->itemsType === 'Preview') {
            $formatted['items'] = $itemData->items ? Image::getImageUrlById($itemData->items) : '';
            $formatted['zoomRatio'] = $itemData->zoomRatio;
        } else {
            $formatted['itemsID'] = $itemData->itemsID;
            $formatted['itemCount'] = $itemData->itemCount;
        }

        return $formatted;
    }

    private function formatButtonPointDraggableData($itemData)
    {
        $formatted = [
            'id' => $itemData->id,
            'button_point_id' => $itemData->button_point_id,
            'DragDropReset' => $itemData->DragDropReset,
            'DragDropRestrict' => $itemData->DragDropRestrict,
        ];

        if ($itemData->DragDropRestrict) {
            $formatted['anchor'] = $itemData->anchor;
            $formatted['target_x'] = $itemData->target_x;
            $formatted['target_y'] = $itemData->target_y;
        } else {
            $formatted['target_button_point_id'] = $itemData->target_button_point_id;
        }

        return $formatted;
    }

    private function formatButtonPointNineSquarecalligraphyGridData($itemData)
    {
        $formatted = [
            'id' => $itemData->id,
            'button_point_id' => $itemData->button_point_id,
            'blankGrid' => $itemData->blankGrid,
            'initOrder' => $itemData->initOrder,
            'order' => $itemData->order,
            'paddingImage' => $itemData->paddingImage ? Image::getImageUrlById($itemData->paddingImage) : '',
        ];

        if ($itemData->blankGrid) {
            $formatted['compoundImage'] = $itemData->compoundImage ? Image::getImageUrlById($itemData->compoundImage) : '';
        }

        return $formatted;
    }
}
