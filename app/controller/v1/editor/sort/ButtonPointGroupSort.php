<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Db;
use think\facade\Validate;

class ButtonPointGroupSort
{
    public function updateButtonPointGroupSort()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'room_id' => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $roomId = (int) $params['room_id'];
        $groupIds = array_values(array_filter(array_map('intval', (array) request()->post('group_ids/a', []))));
        $buttonPointIds = array_values(array_filter(array_map('intval', (array) request()->post('button_point_ids/a', []))));

        if (empty($groupIds) || empty($buttonPointIds)) {
            return error('参数错误', 400);
        }

        $groupCount = Db::name('button_point_group')
            ->where('room_id', $roomId)
            ->whereIn('id', $groupIds)
            ->count();

        if ($groupCount !== count($groupIds)) {
            return error('分组数据不完整', 404);
        }

        $buttonCount = Db::name('button_point')
            ->where('room_id', $roomId)
            ->whereIn('id', $buttonPointIds)
            ->count();

        if ($buttonCount !== count($buttonPointIds)) {
            return error('按钮点数据不完整', 404);
        }

        Db::startTrans();
        try {
            foreach ($groupIds as $index => $groupId) {
                Db::name('button_point_group')
                    ->where('id', $groupId)
                    ->where('room_id', $roomId)
                    ->update(['sort' => $index]);
            }

            foreach ($buttonPointIds as $index => $buttonPointId) {
                Db::name('button_point')
                    ->where('id', $buttonPointId)
                    ->where('room_id', $roomId)
                    ->update(['sort' => $index]);
            }

            Db::commit();

            return success(true, '分组排序成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('分组排序失败：' . $e->getMessage(), 500);
        }
    }
}
