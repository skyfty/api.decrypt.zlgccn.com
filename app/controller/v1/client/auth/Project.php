<?php

declare(strict_types=1);

namespace app\controller\v1\client\auth;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;
use app\model\StoryPoint\StoryPoint;

class Project
{
    private function reorderButtonPointListByGroupBase(array $items): array
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

    
    public function getProjectCityRoomList()
    {
        $projectId = Request::param('project_id', '');
        $cityId = Request::param('city_id', '');
        if ($projectId === '') return error('项目ID不能为空', 400);

        $city_list = Db::name('city')
            ->where(['project_id' => $projectId])
            ->field(['id', 'name','preset_room_id','image_id'])
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($city_list as &$city) {
            $cityImageInfo = Db::table('image')->where('id', (int)$city['image_id'])->field('file')->find();
            $city['imageUrl'] = $cityImageInfo ? $cityImageInfo['file'] : null;
 
            $room_list = Db::name('room')
                ->where(['cityId' => $city['id']])
                ->field([
                    'id',
                    'name',
                    'imageId',
                    'safeZoneId',
                    'width',
                    'height',
                    'isSave',
                    'isDestroy'
                ])
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            foreach ($room_list as &$room) {
                $room['imageUrl'] = Db::table('image')
                    ->where('id', (int)$room['imageId'])
                    ->field('file')
                    ->find()['file'];
                $room['safeZone'] = Db::table('SafeZone')
                    ->where('id', (int)$room['safeZoneId'])
                    ->withoutField(['projectId', 'name', 'create_time', 'update_time'])
                    ->find();
                $room['isSave'] = (bool) $room['isSave'];
                $room['isDestroy'] = (bool) $room['isDestroy'];
                unset($room['imageId']);
                unset($room['safeZoneId']);
            }

            $city['room_list'] = $room_list;
        }
        
        return success($city_list, '资源获取成功');
    }


    /**
     * 获取当前用户的Project列表
     * GET /api/v1/auth/GetProject
     */
    public function GetProject()
    {
        // 取 GET 参数
        $projectId = Request::param('project_id', '');
        $cityId = Request::param('city_id', '');
        $roomId = Request::param('room_id', '');
        $isHome = filter_var(Request::param('isHome', true), FILTER_VALIDATE_BOOLEAN);

        if ($projectId === '') return error('项目ID不能为空', 400);

        if ($cityId === '') return error('城市ID不能为空', 400);


        $project_list = Db::name('project')
            ->where([
                'id' => $projectId,
                'status' => 1
            ])
            ->field(['id', 'name'])
            ->select()
            ->toArray();
        foreach ($project_list as &$project) {

            $city_list = Db::name('city')
                ->where(['id' => $cityId, 'project_id' => $projectId])
                ->field(['id', 'name','preset_room_id','image_id'])
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            foreach ($city_list as &$city) {
                
                $cityImageInfo = Db::table('image')->where('id', (int)$city['image_id'])->field('file')->find();
                $city['imageUrl'] = $cityImageInfo ? $cityImageInfo['file'] : null;

                if (!$isHome) {
                    if ($roomId === '') return error('房间ID不能为空', 400);
                    $room_list = Db::name('room')
                        ->where(['id' => $roomId, 'cityId' => $cityId])
                        ->field([
                            'id',
                            'name',
                            'imageId',
                            'safeZoneId',
                            'width',
                            'height',
                            'isSave',
                            'isDestroy'
                        ])
                        ->order('sort', 'asc')
                        ->select()
                        ->toArray();

                    // 修改Room参数
                    foreach ($room_list as &$room) {
                        $room['imageUrl'] = Db::table('image')
                            ->where('id', (int)$room['imageId'])
                            ->field('file')
                            ->find()['file'];
                        $room['safeZone'] = Db::table('SafeZone')
                            ->where('id', (int)$room['safeZoneId'])
                            ->withoutField(['projectId', 'name', 'create_time', 'update_time'])
                            ->find();
                        $room['isSave'] = (bool) $room['isSave'];
                        $room['isDestroy'] = (bool) $room['isDestroy'];
                        unset($room['imageId']);
                        unset($room['safeZoneId']);

                    }
                    // 添加BottomPoint
                    foreach ($room_list as &$room) {
                        $button_point_list = Db::name('button_point')
                            ->where('room_id', (int)$room['id'])
                            ->order('sort', 'asc')
                            ->withoutField(['name', 'image_id', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();

                        foreach ($button_point_list as &$buttonPoint) {
                            $buttonPoint['audio_list'] = $this->getButtonPointAudioList((int)$buttonPoint['id']);

                            if ($buttonPoint['resource_type'] === 0 && $buttonPoint['sub_resource_type'] === 0) {
                                unset($buttonPoint['animation_action']);
                                unset($buttonPoint['spine']);
                                $imageInfo = Db::table('image')->where('id', (int)$buttonPoint['resource_id'])
                                    ->field('file')->find();
                                $buttonPoint['imageUrl'] = $imageInfo ? $imageInfo['file'] : null;
                            } else if ($buttonPoint['resource_type'] === 0 && $buttonPoint['sub_resource_type'] === 2) {
                                unset($buttonPoint['spine']);
                                $buttonPoint['animation_action_id'] = $buttonPoint['animation_action'];
                                $imageInfo = Db::table('animation_frames')
                                    ->where('animation_action_id', $buttonPoint['animation_action'])
                                    ->field('frameImage')
                                    ->order('sort', 'asc')
                                    ->select()
                                    ->toArray();
                                $buttonPoint['animation_action'] = $imageInfo;
                            } else if ($buttonPoint['resource_type'] === 0 && $buttonPoint['sub_resource_type'] === 3) {
                                unset($buttonPoint['animation_action']);
                                $imageInfo = Db::table('image')->where('id', (int)$buttonPoint['resource_id'])
                                    ->field('file')->find();
                                $buttonPoint['imageUrl'] = $imageInfo ? $imageInfo['file'] : null;
                            }

                            $buttonPoint['wxSafeArea'] = (bool) $buttonPoint['wxSafeArea'];
                            $buttonPoint['multiLanguage'] = (bool)  $buttonPoint['multiLanguage'];


                            $typeToTableMap = [
                                1 => 'button_point_tip',
                                2 => 'button_point_draggable',
                                3 => 'button_point_rotate',
                                4 => 'button_point_move',
                                5 => 'button_point_nineSquarecalligraphyGrid',
                                7 => 'button_point_set',
                                9 => 'button_point_door',
                                10 => 'button_point_item',
                            ];
                            $table = $typeToTableMap[$buttonPoint['type']] ?? null;

                            if ($table) {
                                switch ($buttonPoint['type']) {
                                    case 2:
                                        $dragDropResetRecord = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->field('DragDropRestrict')
                                            ->find();

                                        $fieldsToExclude = $dragDropResetRecord['DragDropRestrict'] === 0
                                            ? ['anchor', 'target_x', 'target_y']
                                            : ['target_button_point_id'];

                                        $param = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->withoutField($fieldsToExclude)
                                            ->find();
                                        $param['DragDropReset'] = (bool) $param['DragDropReset'];
                                        $param['DragDropRestrict'] = (bool) $param['DragDropRestrict'];
                                        $buttonPoint['param'] = $param;
                                        break;
                                    case 5:
                                        $blankGridRecord = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->field('blankGrid')
                                            ->find();

                                        $fieldsToExclude = $blankGridRecord['blankGrid'] === 0 ? ['compoundImage'] : [];

                                        $param = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->withoutField($fieldsToExclude)
                                            ->find();

                                        $param['blankGrid'] = (bool) $param['blankGrid'];
                                        $param['paddingImage'] = Db::table('image')
                                            ->where('id', (int)$param['paddingImage'])
                                            ->field('file')
                                            ->find()['file'];
                                        if ($param['blankGrid']) {
                                            $param['compoundImage'] = Db::table('image')
                                                ->where('id', (int)$param['compoundImage'])
                                                ->field('file')
                                                ->find()['file'];
                                        }
                                        $buttonPoint['param'] = $param;
                                        break;
                                    case 9:
                                        $doorTypeRecord = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->field('doorType')
                                            ->find();
                                        $fieldsToExclude = [
                                            'id',
                                            'button_point_id',
                                            'isOpen',
                                            'doorCityId',
                                            'doorRoomId',
                                            'successVoice',
                                            'errorVoice',
                                            'doorType'
                                        ];
                                        switch ($doorTypeRecord['doorType']) {
                                            case 'BasicDoor':
                                                $fieldsToExclude = array_merge($fieldsToExclude, ['itemsID', 'itemCount', 'lockText']);
                                                break;
                                            case 'NumericCodeDoor':
                                                $fieldsToExclude = array_merge($fieldsToExclude, ['password', 'count', 'lockText']);
                                                break;
                                            case 'AlphaKeyDoor':
                                                $fieldsToExclude = array_merge($fieldsToExclude, ['password', 'count', 'lockText']);
                                                break;
                                            case 'DraggableDoor':
                                                $fieldsToExclude = array_merge($fieldsToExclude, ['moveOrientation', 'moveDistance']);
                                                break;
                                            case 'LogicDoor':
                                                $fieldsToExclude = array_merge($fieldsToExclude, ['pointAnchors', 'pointX', 'pointY']);
                                                break;
                                        }

                                        $param = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->field($fieldsToExclude)
                                            ->find();

                                        $param['isOpen'] = (bool) $param['isOpen'];
                                        $param['successVoice'] = Db::table('audio')
                                            ->where('id', (int)$param['successVoice'])
                                            ->field('file')
                                            ->find()['file'];
                                        $param['errorVoice'] = Db::table('audio')
                                            ->where('id', (int)$param['errorVoice'])
                                            ->field('file')
                                            ->find()['file'];
                                        $buttonPoint['param'] = $param;
                                        break;
                                    case 10:
                                        $itemsTypeRecord = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->field('itemsType')
                                            ->find();

                                        switch ($itemsTypeRecord['itemsType']) {
                                            case 'PickUp':
                                                $fieldsToExclude = [...['items', 'zoomRatio']];
                                                break;
                                            case 'Preview':
                                                $fieldsToExclude = [...['itemsID', 'itemCount']];
                                                break;
                                        }

                                        $param = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->withoutField($fieldsToExclude)
                                            ->find();

                                        if ($itemsTypeRecord['itemsType'] == 'Preview') {
                                            $param['items'] = Db::table('image')
                                                ->where('id', (int)$param['items'])
                                                ->field('file')
                                                ->find()['file'];
                                        }
                                        $buttonPoint['param'] = $param;
                                        break;


                                    default:
                                        $param = Db::table($table)
                                            ->where('button_point_id', $buttonPoint['id'])
                                            ->withoutField(['create_time', 'update_time'])
                                            ->find();
                                        $buttonPoint['param'] = $param;
                                        break;
                                }
                            }
                        }
                        unset($buttonPoint);

                        $button_point_list = $this->reorderButtonPointListByGroupBase($button_point_list);

                        $room['buttonPointList'] = $button_point_list;
                    }
                    // 添加HintPoint
                    foreach ($room_list as &$room) {
                        $hint_point_list = Db::name('hint_point')
                            ->where('room_id', (int)$room['id'])
                            ->order('sort', 'asc')
                            ->withoutField(['name', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();

                        foreach ($hint_point_list as &$hintPoint) {
                            $typeToTableMap = [
                                1 => 'hint_point_specialEffect',
                                2 => 'hint_point_scaleUp',
                                3 => 'hint_point_image',
                                4 => 'hint_point_number',
                                5 => 'hint_point_letters',
                            ];
                            $table = $typeToTableMap[$hintPoint['help_type']] ?? null;

                            if ($table) {
                                $param = Db::table($table)
                                    ->where('hint_Point_id', $hintPoint['id'])
                                    ->withoutField(['create_time', 'update_time'])
                                    ->find();

                                if ($hintPoint['help_type'] == 3 && $param) {
                                    $image = Db::table('image')
                                        ->where('id', (int)$param['image_id'])
                                        ->field('file')
                                        ->find();
                                    $param['imageUrl'] = $image['file'] ?? '';
                                    // 删除原始字段
                                    unset($param['image_id']);
                                }

                                $hintPoint['param'] = $param;
                            } else {
                                $hintPoint['param'] = null;
                            }
                        }
                        unset($hintPoint); // 解除引用

                        $room['hintPointList'] = $hint_point_list;
                    }
                    unset($room); // 解除引用

                    // 添加StoryPoint
                    foreach ($room_list as &$room) {
                        $story_variables_list = Db::name('room_story_variables')
                            ->where('room_id', (int)$room['id'])
                            ->withoutField(['room_id', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();
                        $room['storyVariablesList'] = $story_variables_list;
                        
                        $room['storyPointList'] = $this->getListOfPlotPoints($room['id']);
                    }

                    $city['room_list'] = $room_list;
                } else {
                    // 1. 查询该项目的所有标题
                    $panel_title = Db::table('panel_title')
                        ->where('project_id', $projectId)
                        ->withoutField(['content', 'create_time', 'update_time'])
                        ->find();

                    if (!empty($panel_title)) {

                        $panel_title['imageUrl'] = Db::table('image')->where('id', $panel_title['background_id'])->find()['file'];
                        unset($panel_title['background_id']);
                        // 标题数据存在
                        $list_item = Db::table('panel_title_item')
                            ->where('panel_title_id', $panel_title['id'])
                            ->withoutField(['content', 'panel_title_id', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();
                        foreach ($list_item as &$item) {

                            // 处理类型
                            $item['multiLanguage'] = (bool)$item['multiLanguage'];
                            $item['imageUrl'] = Db::table('image')->where('id', $item['background_id'])->find()['file'];
                            unset($item['background_id']);

                            $localizationText = Db::table('panel_title_localizationText')
                                ->where('panel_title_item_id', $item['id'])->withoutField(['panel_title_item_id', 'create_time', 'update_time'])->find();

                            if (!empty($localizationText['content'])) {
                                $item['localizationText'] = $localizationText;
                            }

                            if ($item['button_type'] === 1) {
                                $item['param'] = Db::table('panel_title_start')
                                    ->where('panel_title_item_id', $item['id'])
                                    ->withoutField(['panel_title_item_id', 'create_time', 'update_time'])
                                    ->find();

                                $item['param']['doorCityId'] = !empty($item['param']['city_id']) ? $item['param']['city_id'] : 0;
                                unset($item['param']['city_id']);

                                $item['param']['doorRoomId'] = !empty($item['param']['room_id']) ?  $item['param']['room_id'] : null;
                                unset($item['param']['room_id']);

                                $item['param']['successVoice'] = !empty($item['param']['success_audio']) ?
                                    Db::table('audio')->where('id', $item['param']['success_audio'])->find()['file'] : null;
                                unset($item['param']['success_audio']);

                                $item['param']['errorVoice'] = !empty($item['param']['error_audio']) ?
                                    Db::table('audio')->where('id', $item['param']['error_audio'])->find()['file'] : null;
                                unset($item['param']['error_audio']);
                            }
                        }

                        $panel_title['buttonPointList'] = $list_item;
                        $city['room_list'] = [$panel_title];
                    }
                }
            }

            $project['city_list'] = $city_list;
        }


        return success($project_list, '资源获取成功' . $isHome);
    }


    /**
     * 获取按钮点的音频列表
     * @param int $buttonPointId 按钮点ID
     * @return array
     */
    private function getButtonPointAudioList($buttonPointId)
    {
        if ($buttonPointId <= 0) {
            return [];
        }

        // 查询数据
        $audioList = Db::table('button_point_resources_audio')
            ->where('buttonPoint_id', $buttonPointId)
            ->withoutField(['buttonPoint_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        // 处理音频路径
        foreach ($audioList as &$item) {
            $audio = Db::table('audio')
                ->where('id', $item['resource_id'])
                ->field('file')
                ->find();
            unset($item['resource_id']);
            $item['audio_path'] = $audio['file'] ?? '';
        }

        return $audioList;
    }



    /**
     * 获取剧情点列表
     */
    private function getListOfPlotPoints($roomId){

        if (!$roomId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $query = StoryPoint::with([
            'conditions',
            'actions.assignVariable',
            'actions.operateButtonPoint',
            'actions.requiredConditions'
        ]);

        if ($roomId) {
            $query->where('room_id', $roomId);
        }
        $list = $query->select();
        
        // 创建控制器实例
        $storyPointController = app()->make(\app\controller\v1\client\storyPoint\StoryPointController::class);

        // 格式化数据
        $formattedList = [];
        foreach ($list as $storyPoint) {
            $formattedList[] = $storyPointController->formatStoryPoint($storyPoint);
        }

        return $formattedList;
    }


}
