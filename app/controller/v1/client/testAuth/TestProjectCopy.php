<?php
declare(strict_types=1);

namespace app\controller\v1\client\auth;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class TestProjectCopy
{
    /**
     * 获取当前用户的Project列表
     * GET /api/v1/auth/GetProject
     */
    public function GetTestProjectCopy()
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
                        ->where('id', $cityId)
                        ->field(['id', 'name']) 
                        ->select()
                        ->toArray();

            foreach ($city_list as $k => $city) {
                $room_list = Db::name('room')
                            ->where('id', $roomId)
                            ->field(['id', 'name', 'imageId', 'safeZoneId',
                                'width', 'height', 'isSave', 'isDestroy'])
                            ->order('sort', 'asc')
                            ->select()
                            ->toArray();
                // 修改Room参数
                foreach ($room_list as $k2 => $room){
                    $room_list[$k2]['imageUrl'] = Db::table('image')
                        ->where('id', (int)$room['imageId'])
                        ->field('file')
                        ->find()['file'];
                    $room_list[$k2]['safeZone'] = Db::table('SafeZone')
                        ->where('id', (int)$room['safeZoneId'])
                        ->withoutField(['projectId', 'name', 'create_time', 'update_time'])
                        ->find();
                    $room_list[$k2]['isSave'] = $room['isSave'] === 0 ? false : true;
                    $room_list[$k2]['isDestroy'] = $room['isDestroy'] === 0 ? false : true;
                    unset($room_list[$k2]['imageId']);
                    unset($room_list[$k2]['safeZoneId']);

                    
                    // 1. 查询该项目的所有标题
                    $panel_title_list = Db::table('panel_title')
                        ->where('project_id', $projectId)
                        ->withoutField(['content', 'create_time', 'update_time'])
                        ->find();
                        
                    if (!empty($panel_title_list)) {
                        
                        // 标题数据存在
                        $list_item = Db::table('panel_title_item')
                        ->where('panel_title_id', $panel_title_list['id'])
                        ->withoutField(['content','create_time', 'update_time'])
                        ->select()
                        ->toArray();
                        foreach($list_item as &$item){
                            $item['backgroundUrl'] = Db::table('image') ->where('id', $item['background_id'])->find()['file'];
                            unset($item['background_id']);
                        }
                        $panel_title_list['panel_title_items'] = $list_item;
                        $room_list[$k2]['panel_title'] = $panel_title_list;
                    }

                }
                // 添加BottomPoint
                foreach ($room_list as $k2 => $room) {
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
                            unset($button_point_list[$k3]['image_id']);
                        }
                        $button_point_list[$k3]['wxSafeArea'] = $button_point_list[$k3]['wxSafeArea'] === 0 ? false : true;
                        $button_point_list[$k3]['multiLanguage'] = (bool)  $button_point_list[$k3]['wxSafeArea'];

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
                                    $param['DragDropReset'] = $param['DragDropReset'] === 0 ? false : true;
                                    $param['DragDropRestrict'] = $param['DragDropRestrict'] === 0 ? false : true;
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

                                    $param['blankGrid'] = $param['blankGrid'] === 0 ? false : true;
                                    $param['paddingImage'] = Db::table('image')
                                        ->where('id', (int)$param['paddingImage'])
                                        ->field('file')
                                        ->find()['file'];
                                    if($param['blankGrid']){
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
                                    $fieldsToExclude = ['id', 'button_point_id','isOpen',  
                                        'doorCityId', 'doorRoomId', 'successVoice', 'errorVoice', 
                                        'doorType'];
                                    switch($doorTypeRecord['doorType']){
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
                                    $button_point_list[$k3]['param'] = $param;
                                    break;
                                case 10: 
                                    $itemsTypeRecord = Db::table($table)
                                        ->where('button_point_id', $buttonPoint['id'])
                                        ->field('itemsType')
                                        ->find();

                                    switch($itemsTypeRecord['itemsType']){
                                        case 'PickUp':
                                            $fieldsToExclude = [...['items', 'zoomRatio']];
                                            break;
                                        case 'Preview':
                                            $fieldsToExclude = [...['itemsID', 'itemCount' ]];
                                            break;
                                    }

                                    $param = Db::table($table)
                                        ->where('button_point_id', $buttonPoint['id'])
                                        ->withoutField($fieldsToExclude)
                                        ->find();

                                    if($itemsTypeRecord['itemsType'] == 'Preview'){
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
 

                    $room_list[$k2]['buttonPointList'] = $button_point_list;
                }
                // 添加HintPoint
                foreach ($room_list as $k2 => $room) {
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
                                if($hintPoint['help_type'] == 3){
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
                    $room_list[$k2]['hintPointList'] = $hint_point_list;
                }

                $city_list[$k]['room_list'] = $room_list;
 
            }

            $project['city_list'] = $city_list;
        }

    
        return success($project_list, '资源获取成功');
    }
    
}