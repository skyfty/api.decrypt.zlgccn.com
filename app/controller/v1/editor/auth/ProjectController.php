<?php

declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\facade\Db;
use app\model\Room;
use app\model\ButtonPoint\ButtonPointLocalizationText;

class ProjectController
{
    /**
     * 获取当前用户的Project列表
     * GET /api/v1/auth/GetProject
     */
    public function GetProject()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $project_list = Db::name('project')
            ->where([
                'user_id' => (int)$user['id'],
                'status' => 1
            ])
            ->field(['id', 'name'])
            ->select()
            ->toArray();
        foreach ($project_list as &$project) {
            $city_list = Db::name('city')
                ->where('project_id', (int)$project['id'])
                ->field(['id', 'name', 'preset_room_id', 'image_id'])
                ->select()
                ->toArray();

            foreach ($city_list as $k => $city) {
                $cityImageInfo = Db::table('image')->where('id', (int)$city['image_id'])->field('file')->find();
                $city['imageUrl'] = $cityImageInfo ? $cityImageInfo['file'] : null;
                $room_list = Db::name('room')
                    ->where('cityId', (int)$city['id'])
                    ->field([
                        'id',
                        'cityId',
                        'name',
                        'imageId',
                        'background_audio',
                        'safeZoneId',
                        'width',
                        'height',
                        'room_type',
                        'isSave',
                        'isDestroy'
                    ])
                    ->order('sort', 'asc')
                    ->select()
                    ->toArray();

                foreach ($room_list as $k2 => $room) {
                    $button_point_list = Db::name('button_point')
                        ->where('room_id', (int)$room['id'])
                        ->order('sort', 'asc')
                        ->select()
                        ->toArray();
                    foreach ($button_point_list as $k3 => $buttonPoint) {
                        // $button_point_list[$k3]['hidden'] = false;
                        if ($button_point_list[$k3]['sub_resource_type'] === 2) {
                            $animationActionId = $button_point_list[$k3]['animation_action'];

                            $frameRecord = Db::table('animation_frames')
                                ->where('animation_action_id', $animationActionId)
                                ->field('frameImage')
                                ->order('sort', 'asc')
                                ->find();

                            // 如果 $frameRecord 是 null，或者没有 'frameImage' 键，则使用空字符串
                            $button_point_list[$k3]['animation_action_path'] =
                                ($frameRecord && isset($frameRecord['frameImage']))
                                ? $frameRecord['frameImage']
                                : '';
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
                            case 7:
                                $table = 'button_point_set';
                                break;
                            case 9:
                                $table = 'button_point_door';
                                break;
                            case 10:
                                $table = 'button_point_item';
                                break;
                            case 11:
                                $table = 'button_point_chapter';
                                break;
                        }

                        if ($table) {
                            $param = Db::table($table)
                                ->where('button_point_id', $buttonPoint['id'])
                                ->find();
                            $button_point_list[$k3]['param'] = $param;
                        }
                        $button_point_list[$k3]['localizationText'] = $this->loadLocalizedData($buttonPoint['id']);
                    }
                    $room_list[$k2]['button_point_list'] = $button_point_list;
                }
                foreach ($room_list as $k2 => $room) {
                    $hint_point_list = Db::name('hint_point')
                        ->where('room_id', (int)$room['id'])
                        ->order('sort', 'asc')
                        ->select()
                        ->toArray();
                    foreach ($hint_point_list as $k3 => $hintPoint) {
                        $table = null;
                        switch ($hintPoint['help_type']) {
                            case 1:
                                $table = 'hint_point_specialEffect';
                                break;
                            case 2:
                                $table = 'hint_point_scaleUp';
                                break;
                            case 3:
                                $table = 'hint_point_image';
                                break;
                            case 4:
                                $table = 'hint_point_number';
                                break;
                            case 5:
                                $table = 'hint_point_letters';
                                break;
                        }

                        if ($table) {
                            $param = Db::table($table)
                                ->where('hint_Point_id', $hintPoint['id'])
                                ->withoutField(['create_time', 'update_time'])
                                ->find();
                            $hint_point_list[$k3]['param'] = $param;
                        }
                    }
                    $room_list[$k2]['hint_point_list'] = $hint_point_list;
                }

                $city_list[$k]['room_list'] = $room_list;
            }

            $project['city_list'] = $city_list;
        }


        return success($project_list, '资源获取成功');
    }

    private function loadLocalizedData($button_point_id)
    {
        $localizationText = ButtonPointLocalizationText::where('button_point_id', $button_point_id)->find();

        // 如果记录不存在，则创建并立即重新查询
        if (!$localizationText) {
            ButtonPointLocalizationText::create(['button_point_id' => $button_point_id]);
            $localizationText = ButtonPointLocalizationText::where('button_point_id', $button_point_id)->find();
        }

        return $localizationText;
    }
}
