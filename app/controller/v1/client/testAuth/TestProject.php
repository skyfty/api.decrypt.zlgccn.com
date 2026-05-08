<?php

declare(strict_types=1);

namespace app\controller\v1\client\auth;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class TestProject
{
    /**
     * 获取当前用户的Project列表
     * GET /api/v1/auth/GetProject
     */
    public function GetTestProject()
    {
        // 取 GET 参数
        $projectId = Request::param('project_id', '');
        $cityId = Request::param('city_id', '');
        $roomId = Request::param('room_id', '');

        if ($projectId === '') return error('项目ID不能为空', 400);

        if ($cityId === '') return error('城市ID不能为空', 400);

        if ($roomId === '') return error('房间ID不能为空', 400);

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
                ->field(['id', 'name'])
                ->select()
                ->toArray();

            foreach ($city_list as &$city) {
                $room_list = Db::name('room')
                    ->where(['id' => $roomId, 'cityId' => $cityId])
                    ->field([
                        'id', 'name', 'imageId',
                        'safeZoneId', 'width', 'height',
                        'isSave', 'isDestroy'
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
                        ->withoutField(['name', 'create_time', 'update_time'])
                        ->select()
                        ->toArray();

                    foreach ($button_point_list as $k3 => $buttonPoint) {

                        if ($button_point_list[$k3]['image_id']) {
                            $imageInfo = Db::table('image')->where('id', (int)$buttonPoint['image_id'])
                                ->field('file')->find();
                            $button_point_list[$k3]['imageUrl'] = $imageInfo ? $imageInfo['file'] : null;
                        }
                        if ($button_point_list[$k3]['resource_id']) {
                            $resource_table_name = 'image';
                            if ($button_point_list[$k3]['sub_resource_type'] === 0) {
                                $resource_table_name = $button_point_list[$k3]['resource_type'] === 0 ? 'image' : 'audio';
                            } else if ($button_point_list[$k3]['sub_resource_type'] === 1) {
                                $resource_table_name = $button_point_list[$k3]['resource_type'] === 0 ? 'video' : '';
                            } else if ($button_point_list[$k3]['sub_resource_type'] === 2) {
                                $resource_table_name = $button_point_list[$k3]['resource_type'] === 0 ? 'animations' : '';
                            }
                            $imageInfo = Db::table($resource_table_name)->where('id', (int)$buttonPoint['resource_id'])->find();
                            $button_point_list[$k3]['resourceUrl'] = $imageInfo ? $imageInfo['file'] : null;
                        }

                        $button_point_list[$k3]['wxSafeArea'] = (bool) $button_point_list[$k3]['wxSafeArea'];
                        $button_point_list[$k3]['multiLanguage'] = (bool)  $button_point_list[$k3]['multiLanguage'];


                        $typeToTableMap = [
                            1 => 'button_point_tip',
                            2 => 'button_point_draggable',
                            3 => 'button_point_rotate',
                            4 => 'button_point_move',
                            5 => 'button_point_nineSquarecalligraphyGrid',
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
                                    $button_point_list[$k3]['param'] = $param;
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
                                    $button_point_list[$k3]['param'] = $param;
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
                                    $button_point_list[$k3]['param'] = $param;
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
                                    $button_point_list[$k3]['param'] = $param;
                                    break;


                                default:
                                    $param = Db::table($table)
                                        ->where('button_point_id', $buttonPoint['id'])
                                        ->withoutField(['create_time', 'update_time'])
                                        ->find();
                                    $button_point_list[$k3]['param'] = $param;
                                    break;
                            }
                        }
                    } 

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
                }
                

                $city['room_list'] = $room_list;
                
                // 1. 查询该项目的所有标题
                $panel_title = Db::table('panel_title')
                    ->where('project_id', $projectId)
                    ->withoutField(['content', 'create_time', 'update_time'])
                    ->find();

                if (!empty($panel_title)) {
                    
                    $panel_title['backgroundUrl'] = Db::table('image')->where('id', $panel_title['background_id'])->find()['file'];
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
                        $item['backgroundUrl'] = Db::table('image')->where('id', $item['background_id'])->find()['file'];
                        unset($item['background_id']);
                        
                        $item['localizationText'] = Db::table('panel_title_localizationText')
                            ->where('panel_title_item_id', $item['id'])->find();
                        if ($item['button_type'] === 1) {
                            $item['param'] = Db::table('panel_title_start')
                                ->where('panel_title_item_id', $item['id'])->find();
                        }
                    }
                    
                    $panel_title['buttonPointList'] = $list_item;
                    array_unshift($city['room_list'], $panel_title);
                }
            }

            $project['city_list'] = $city_list;
        }


        return success($project_list, '资源获取成功');
    }
}
