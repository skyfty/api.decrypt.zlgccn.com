<?php

namespace app\controller\v1\editor\buttonPoint;

use think\facade\Request;
use think\facade\Db;
use app\support\ButtonPointBuilder;
use think\facade\Validate;
use app\model\ButtonPoint\ButtonPointLocalizationText;

class ButtonPoint
{
    public function updateButtonPointGroup()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'id' => 'require|number',
            'room_id' => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $buttonPointId = (int) $params['id'];
        $roomId = (int) $params['room_id'];
        $groupValue = $params['button_point_group_id'] ?? null;
        $groupId = ($groupValue === null || $groupValue === '' || $groupValue === 'null')
            ? null
            : (int) $groupValue;

        Db::startTrans();
        try {
            $buttonPoint = Db::table('button_point')->where('id', $buttonPointId)->find();
            if (! $buttonPoint) {
                return error('按钮点不存在', 404);
            }

            if ((int) $buttonPoint['room_id'] !== $roomId) {
                return error('按钮点不属于当前房间', 400);
            }

            if ($groupId !== null) {
                $group = Db::table('button_point_group')
                    ->where('id', $groupId)
                    ->where('room_id', $roomId)
                    ->find();

                if (! $group) {
                    return error('分组不存在或不属于当前房间', 404);
                }
            }

            Db::table('button_point')
                ->where('id', $buttonPointId)
                ->update([
                    'button_point_group_id' => $groupId,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            Db::commit();

            return success([
                'id' => $buttonPointId,
                'button_point_group_id' => $groupId,
            ], $groupId === null ? '已移回根组' : '已移入分组');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('按钮点分组更新失败：' . $e->getMessage(), 500);
        }
    }

    public function newSaveButtonPoint()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 验证请求数据
        $params = request()->post();
        $validate = Validate::rule([
            'room_id' => 'require'
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError());
        }

        $id     = (int)Request::post('id', 0);
        $param  = Request::post('param', []);
        $localizationText  = Request::post('localizationText', []);
        $type   = (int)Request::post('type', 1);

        $data = [
            'room_id'     => (int)Request::post('room_id', 0),
            'button_point_group_id' => Request::post('button_point_group_id', null),
            'image_id'     => Request::post('image_id'),
            'sort'        => (int)Request::post('sort', 0),
            'name'        => Request::post('name', 'newButtonPoint'),
            'width'       => Request::post('width', 200),
            'height'      => Request::post('height', 200),
            'x'           => Request::post('x', 0),
            'y'           => Request::post('y', 0),
            'anchors'     => Request::post('anchors', 5),
            'wxSafeArea'  => Request::post('wxSafeArea', 0),
            'hidden'  => Request::post('hidden', 0),
            'multiLanguage' => Request::post('multiLanguage', 0),
            'status' => Request::post('status', 0),
            'resource_type' => Request::post('resource_type', 0),
            'sub_resource_type' => Request::post('sub_resource_type', 0),
            'resource_id' => Request::post('resource_id'),
            'animation_action' => Request::post('animation_action', 0),
            'animation_play_count' => Request::post('animation_play_count', 0),
            'spine' => Request::post('spine', null),
            'variable_source'        => Request::post('variable_source', null),
            'variable_id'        => Request::post('variable_id', 0),
            'variable_value'        => Request::post('variable_value', 0),
            'variable_operation_type'   => Request::post('variable_operation_type', null),
            'variable_interact_type'   => Request::post('variable_interact_type', 0),

            'link_button'        => (int)Request::post('link_button', 0),
            'type'        => $type,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        Db::startTrans();
        try {
            if ($id === 0) {
                // 创建
                $data['sort']        = Db::table('button_point')->where('room_id', $data['room_id'])->count();
                $data['create_time'] = date('Y-m-d H:i:s');
                $newId = Db::table('button_point')->insertGetId($data);
                ButtonPointBuilder::sync($newId, $type, $param);
                $this->createLocalizedData($newId);
            } else {
                // 更新
                $originalType = Db::table('button_point')->where('id', $id)->value('type');
                Db::table('button_point')->where('id', $id)->update($data);

                if ($originalType != $type) {
                    ButtonPointBuilder::delete($id, $originalType);
                    ButtonPointBuilder::sync($id, $type, $param);
                } else {
                    $subId = ButtonPointBuilder::subId($id, $type);
                    ButtonPointBuilder::sync($id, $type, $param, $subId);
                }
                $this->updateLocalizedData($localizationText);
            }
            Db::commit();
            return success(true, $id ? '配置同步成功' : '新建成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error($id ? '配置同步失败' : '新建失败' . $e->getMessage(), 500);
        }
    }

    private function createLocalizedData($newId)
    {
        try {
            $LocalizationText = new ButtonPointLocalizationText();
            $LocalizationText->button_point_id = $newId;
            $LocalizationText->save();
        } catch (\Throwable $e) {
            return error($e);
        }
    }

    private function updateLocalizedData($localizationText)
    {
        if(empty($localizationText['content'])) $localizationText['content'] = null;
        $result = ButtonPointLocalizationText::where('id', $localizationText['id'])->update($localizationText);

        if (!$result) {
            return error('数据未有变更', 400);
        }else{
            return success($result);
        }
    }
}
