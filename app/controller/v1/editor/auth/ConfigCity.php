<?php
declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\exception\ValidateException;
use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;

class ConfigCity
{
    private function buildCloneName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '副本';
        }

        if (preg_match('/-副本$/u', $name)) {
            return $name;
        }

        return $name . '-副本';
    }

    /**
     * 克隆城市
     */
    public function cloneCity()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $cityId = Request::post('city_id/d');
        if (empty($cityId)) {
            return error('城市ID不能为空', 400);
        }

        $sourceCity = Db::name('city')->where('id', $cityId)->find();
        if (empty($sourceCity)) {
            return error('城市不存在', 404);
        }

        $cloneName = trim((string) Request::post('name/s', ''));
        if ($cloneName === '') {
            $cloneName = $this->buildCloneName((string) ($sourceCity['name'] ?? 'city'));
        }

        Db::startTrans();
        try {
            $newCityRow = $sourceCity;
            unset($newCityRow['id'], $newCityRow['create_time'], $newCityRow['update_time']);
            $newCityRow['name'] = $cloneName;
            $newCityRow['create_time'] = date('Y-m-d H:i:s');
            $newCityRow['update_time'] = date('Y-m-d H:i:s');

            $newCityId = Db::name('city')->insertGetId($newCityRow);
            if ($newCityId <= 0) {
                throw new \Exception('城市克隆失败');
            }

            $roomList = Db::name('room')
                ->where('cityId', $cityId)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            $roomIdMap = [];
            foreach ($roomList as $room) {
                $newRoomRow = $room;
                unset($newRoomRow['id'], $newRoomRow['create_time'], $newRoomRow['update_time']);
                $newRoomRow['cityId'] = $newCityId;
                $newRoomRow['create_time'] = date('Y-m-d H:i:s');
                $newRoomRow['update_time'] = date('Y-m-d H:i:s');

                $newRoomId = Db::name('room')->insertGetId($newRoomRow);
                if ($newRoomId <= 0) {
                    throw new \Exception('房间克隆失败');
                }

                $roomIdMap[(int) $room['id']] = (int) $newRoomId;
            }

            if (!empty($sourceCity['preset_room_id']) && isset($roomIdMap[(int) $sourceCity['preset_room_id']])) {
                Db::name('city')->where('id', $newCityId)->update([
                    'preset_room_id' => $roomIdMap[(int) $sourceCity['preset_room_id']],
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }

            $buttonPointMap = [];
            $stats = [
                'room' => 0,
                'button_point_group' => 0,
                'button_point' => 0,
                'button_point_param' => 0,
                'button_point_localizationText' => 0,
                'hint_point' => 0,
                'hint_point_condition' => 0,
                'hint_point_param' => 0,
                'story_point' => 0,
                'story_point_condition' => 0,
                'story_point_action' => 0,
                'story_point_action_param' => 0,
                'story_point_action_required_condition' => 0,
                'room_story_variables' => 0,
                'room_option_group' => 0,
                'room_option_group_option' => 0,
                'room_option_group_option_action' => 0,
                'room_option_group_option_action_param' => 0,
            ];

            foreach ($roomList as $room) {
                $targetRoomId = $roomIdMap[(int) $room['id']];
                $childStats = (new ConfigRoom())->cloneRoomCoreData(
                    (int) $room['id'],
                    (int) $targetRoomId,
                    $roomIdMap,
                    $buttonPointMap,
                    (int) $cityId,
                    (int) $newCityId
                );

                $stats['room']++;
                foreach ($childStats as $key => $value) {
                    if (!isset($stats[$key])) {
                        $stats[$key] = 0;
                    }
                    $stats[$key] += (int) $value;
                }
            }

            foreach ($roomList as $room) {
                $targetRoomId = $roomIdMap[(int) $room['id']];
                $childStats = (new ConfigRoom())->cloneRoomDependentData(
                    (int) $room['id'],
                    (int) $targetRoomId,
                    $roomIdMap,
                    $buttonPointMap,
                    (int) $cityId,
                    (int) $newCityId
                );

                foreach ($childStats as $key => $value) {
                    if (!isset($stats[$key])) {
                        $stats[$key] = 0;
                    }
                    $stats[$key] += (int) $value;
                }
            }

            Db::commit();
            return success([
                'id' => $newCityId,
                'city_id' => $newCityId,
                'room_map' => $roomIdMap,
                'stats' => $stats,
            ], '城市克隆成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('城市克隆失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 新建或更新城市
     */
    public function saveCity()
    {
        // 获取当前登录用户信息
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        
        // 获取参数
        $id = Request::post('id/d', 0); // 转为整数，默认0表示新增
        $project_id = Request::post('project_id/d');
        $name = Request::post('name/s');
        $preset_room_id = Request::post('preset_room_id/d');
        $image_id = Request::post('image_id/d');

        // 验证请求参数
        if (empty($project_id)) {
            return error('城市ID不能为空', 400);
        }
        
        $project = Db::name('project')->find($project_id);
        if (empty($project)) {
            return error('项目不存在', 403);
        }

        if (empty($name)) {
            return error('城市名称不能为空', 400);
        }

        
        // 验证权限
        if ($id > 0) {
            $city = Db::name('city')->find($id);
            if (empty($city)) {
                return error('无权操作该城市', 403);
            }
        }
        
        try {
            Db::startTrans();
            
            if ($id > 0) {
                // 更新城市
                $result = Db::name('city')
                    ->where('id', $id)
                    ->update([
                        'name' => $name,
                        'preset_room_id' => $preset_room_id,
                        'image_id' => $image_id,
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                
                if ($result === false) {
                    throw new \Exception('城市更新失败');
                }
                $projectId = $id;
            } else {
                // 新增城市
                $projectId = Db::name('city')->insertGetId([
                    'project_id' => $project_id,
                    'name' => $name,
                    'preset_room_id' => $preset_room_id,
                    'image_id' => $image_id,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                
                if ($projectId <= 0) {
                    throw new \Exception('城市创建失败');
                }
            }
            
            Db::commit();
            return success([
                'id' => $projectId,
                'message' => $id > 0 ? '城市更新成功' : '城市创建成功'
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 500);
        }
    }
    
}