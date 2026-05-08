<?php
declare(strict_types=1);

namespace app\controller\v1\editor\buttonPoint;

use think\facade\Request;
use think\facade\Db;

class ButtonPointController
{
    /**
     * 新增或更新 ButtonPoint 参数
     * POST /api/v1/auth/saveButtonPoint
     */
    public function saveButtonPoint()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $table = 'button_point';
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$table}'");
        if (empty($exists)) {
            return error("数据表类型不存在", 404);
        }

        $id = (int)Request::post('id', 0);

        // 收集字段，字段名不变
        $data = [
            'room_id'    => (int)Request::post('room_id', 0),
            'item_id'    => (int)Request::post('item_id', ''),
            'sort'   => (int)Request::post('sort', 0),
            'name'      => Request::post('name', 'unnamed'),
            'width'      => Request::post('width', 200),
            'height'     => Request::post('height', 200),
            'x'          => Request::post('x', 0),
            'y'          => Request::post('y', 0),
            'anchors'    => Request::post('anchors', 5),
            'wxSafeArea' => Request::post('wxSafeArea', 0),
            'status' => Request::post('status', 0),
            'type'       => Request::post('type', 1),
            'content'    => Request::post('content', ''),
            'update_time'=> date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 更新
            $result = Db::name($table)->where('id', $id)->update($data);
            if ($result === false) {
                return error('配置更新失败', 500);
            }
            return success(['id' => $id], '配置更新成功');
        } else {
            // 新增
            $data['create_time'] = date('Y-m-d H:i:s');
            // 新增时 sequence 可根据 room_id 数量+1
            if ($data['sort'] === 0 && $data['room_id'] > 0) {
                $count = Db::name($table)->where('room_id', $data['room_id'])->count();
                $data['sort'] = $count + 1;
            } 
            $newId = Db::name($table)->insertGetId($data);
            if (!$newId) {
                return error('配置添加失败', 500);
            }
            return success(['id' => $newId], '配置添加成功');
        }
    }
}