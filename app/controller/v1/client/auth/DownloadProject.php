<?php

declare(strict_types=1);

namespace app\controller\v1\client\auth;

use think\Response;
use think\facade\Db;
use think\facade\Request;

class DownloadProject
{
    /**
     * 下载Project数据的JSON文件，包含项目下所有城市和房间
     * GET /api/v1/client/auth/DownloadProjectJson?project_id=xxx
     */
    public function DownloadProjectJson()
    {
        // 获取GET参数并强制转换为整数
        $project_id = Request::get('project_id', 0, 'intval');

        // 参数校验
        if ($project_id <= 0) {
            return Response::create(['msg' => '项目Id不能为空或无效！'], 'json', 401);
        }

        // 查询项目
        $project = Db::table('project')->where('id', $project_id)
            ->field('id, name') // 指定需要的字段
            ->find();
        if (!$project) {
            return Response::create(['msg' => '项目不存在！'], 'json', 404);
        }

        // 查询项目下的所有城市
        $cities = Db::table('city')->where('project_id', $project_id)
            ->field('id, name') // 指定需要的字段
            ->select()
            ->toArray();

        if (empty($cities)) {
            return Response::create(['msg' => '该项目下没有城市数据！'], 'json', 404);
        }

        // 处理每个城市及其包含的所有房间
        foreach ($cities as &$city) {
            // 查询城市下的所有房间
            $rooms = Db::table('room')->where('cityId', $city['id'])
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

            if (!empty($rooms)) {
                // 处理每个房间的数据
                foreach ($rooms as &$room) {

                    $room['imageUrl'] = Db::table('image')
                        ->where('id', (int)$room['imageId'])
                        ->field('file')
                        ->find()['file'];
                    $room['safeZone'] = Db::table('SafeZone')
                        ->where('id', (int)$room['safeZoneId'])
                        ->withoutField(['projectId', 'name', 'create_time', 'update_time'])
                        ->find();
                    $room['isSave'] = $room['isSave'] === 0 ? false : true;
                    $room['isDestroy'] = $room['isDestroy'] === 0 ? false : true;

                    unset($room['imageId']);
                    unset($room['safeZoneId']);


                    // 查询button_point数据
                    $button_point_list = Db::table('button_point')
                        ->where('room_id', $room['id'])
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

                        $buttonPoint['wxSafeArea'] = $buttonPoint['wxSafeArea'] === 0 ? false : true;
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
                                    $param['DragDropReset'] = $param['DragDropReset'] === 0 ? false : true;
                                    $param['DragDropRestrict'] = $param['DragDropRestrict'] === 0 ? false : true;
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

                                    $param['blankGrid'] = $param['blankGrid'] === 0 ? false : true;
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

                                    $param['isOpen'] = $param['isOpen'] === 0 ? false : true;
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
                    $room['buttonPointList'] = $button_point_list;

                    // 查询hint_point数据
                    $hint_point_list = Db::table('hint_point')
                        ->where('room_id', $room['id'])
                        ->order('sort', 'asc')
                        ->withoutField(['name', 'create_time', 'update_time'])
                        ->select()
                        ->toArray();

                    foreach ($hint_point_list as $k3 => $hintPoint) {
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
                            if ($hintPoint['help_type'] == 3) {
                                $param['imageUrl'] = Db::table('image')
                                    ->where('id', (int)$param['image_id'])
                                    ->field('file')
                                    ->find()['file'];
                                // 删除原始字段
                                unset($param['image_id']);
                            }
                            $hint_point_list[$k3]['param'] = $param;
                        }
                    }
                    $room['hintPointList'] = $hint_point_list;

                    // 添加StoryPoint
                    $story_variables_list = Db::name('room_story_variables')
                        ->where('room_id', (int)$room['id'])
                        ->withoutField(['room_id', 'create_time', 'update_time'])
                        ->select()
                        ->toArray();
                    $room['storyVariablesList'] = $story_variables_list;
                }

                // 将所有房间添加到城市数据中
                $city['room_list'] = $rooms;



                // 1. 查询该项目的所有标题
                $panel_title = Db::table('panel_title')
                    ->where('project_id', $project_id)
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
                    array_unshift($city['room_list'], $panel_title);
                }
            } else {
                $city['room_list'] = [];
            }
        }

        // 将所有城市添加到项目数据中
        $project['city_list'] = $cities;
        $projectList = [$project];

        // 生成文件名
        $filename = 'project_data_' . date('Ymd_His') . '.json';

        // 输出JSON数据并设置为文件下载
        return response()
            ->data(json_encode(
                $projectList,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )) // 格式化+保留中文
            ->header([
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache'
            ]);
    }


    /**
     * 获取按钮点的音频列表
     * @param int $buttonPointId 按钮点ID
     * @return array
     */
    private function getButtonPointAudioList($buttonPointId)
    {
        if ($buttonPointId <= 0) {
            return null;
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
}
