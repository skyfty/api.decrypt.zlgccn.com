<?php

declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\facade\Request;
use think\facade\Db;

class ConfigRoom
{
    
    /**
     * 获取 ButtonPoint 详情
     */
    public function room_detail()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        $room_id = request()->param('room_id');

        $room_data = Db::name('room')
            ->where('id', $room_id)
            ->field([
                'id',
                'cityId',
                'name',
                'imageId',
                'safeZoneId',
                'width',
                'height',
                'room_type',
                'isSave',
                'isDestroy'
            ])
            ->order('sort', 'asc')
            ->find();
        
        $button_point_list = Db::name('button_point')
            ->where('room_id', $room_id)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        foreach ($button_point_list as &$buttonPoint) {
            if ($buttonPoint['sub_resource_type'] === 2) {
                $animationActionId = $buttonPoint['animation_action'];

                $frameRecord = Db::table('animation_frames')
                    ->where('animation_action_id', $animationActionId)
                    ->field('frameImage')
                    ->order('sort', 'asc')
                    ->find();

                $buttonPoint['animation_action_path'] =
                    ($frameRecord && isset($frameRecord['frameImage']))
                    ? $frameRecord['frameImage']
                    : '';
                // $buttonPoint['hidden'] = false;
            }

            $table = null;
            switch ($buttonPoint['type']) {
                case 1:
                    $table = 'button_point_tip';
                    break;
                case 2:
                    $table = 'button_point_draggable';
                    break;
                case 3:
                    $table = 'button_point_rotate';
                    break;
                case 4:
                    $table = 'button_point_move';
                    break;
                case 5:
                    $table = 'button_point_nineSquarecalligraphyGrid';
                    break;
                case 9:
                    $table = 'button_point_door';
                    break;
                case 10:
                    $table = 'button_point_item';
                    break;
            }
            
            if ($table) {
                $param = Db::table($table)
                    ->where('button_point_id', $buttonPoint['id'])
                    ->find();
                $buttonPoint['param'] = $param;
            }
            
        }

        $room_data['button_point_list'] = $button_point_list;
        return success($room_data, '房间详情');
    }

    /**
     * 新建或更新房间
     */
    public function saveRoom()
    {
        // 获取当前登录用户信息
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 获取参数
        $id = Request::post('id/d', 0); // 转为整数，默认0表示新增
        $cityId = Request::post('cityId/d');
        $name = Request::post('name/s');
        $imageId = Request::post('imageId/d');
        $safeZoneId = Request::post('safeZoneId/d');
        $width = Request::post('width/d');
        $height = Request::post('height/d');
        $room_type = Request::post('room_type');
        $isSave = Request::post('isSave/d');
        $isDestroy = Request::post('isDestroy/d');

        // 验证请求参数
        if (empty($cityId)) {
            return error('城市ID不能为空', 400);
        }

        $city = Db::name('city')->find($cityId);
        if (empty($city)) {
            return error('城市不存在', 403);
        }

        if (empty($name)) {
            return error('房间名称不能为空', 400);
        }


        // 验证权限
        if ($id > 0) {
            $room = Db::name('room')->find($id);
            if (empty($room)) {
                return error('无权操作该房间', 403);
            }
        }

        $row = [
            'cityId' => $cityId,
            'name' => $name,
            'width' => $width,
            'height' => $height,
            'imageId' => $imageId,
            'background_audio' => Request::post('background_audio/d', 0),
            'safeZoneId' => $safeZoneId,
            'room_type' => $room_type,
            'isSave' => $isSave,
            'isDestroy' => $isDestroy,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];


        try {
            Db::startTrans();

            if ($id > 0) {
                // 更新房间
                $result = Db::name('room')
                    ->where('id', $id)
                    ->update($row);

                if ($result === false) {
                    throw new \Exception('房间更新失败');
                }
                $projectId = $id;
            } else {
                // 当前 room 里已有多少条记录
                $maxSort = Db::table('room')
                    ->where('cityId', $cityId)
                    ->count();
                // 新增房间
                $row['sort'] = $maxSort;
                $row['create_time'] = date('Y-m-d H:i:s');
                $projectId = Db::name('room')->insertGetId($row);

                if ($projectId <= 0) {
                    throw new \Exception('房间创建失败');
                }
            }

            Db::commit();
            return success(true, $id > 0 ? '房间更新成功' : '房间创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 500);
        }
    }

}
