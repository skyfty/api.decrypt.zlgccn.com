<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Db;
use think\facade\Validate;

class TopLevelEntrySort
{
    public function updateTopLevelEntrySort()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'room_id' => 'require|number',
            'entries' => 'require',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $roomId = (int) $params['room_id'];
        $entries = (array) request()->post('entries/a', []);

        if (empty($entries)) {
            return error('参数错误', 400);
        }

        $groupIds = [];
        $buttonPointIds = [];

        foreach ($entries as $entry) {
            $type = strtolower((string) ($entry['type'] ?? ''));
            $id = (int) ($entry['id'] ?? 0);

            if ($id <= 0) {
                return error('参数错误', 400);
            }

            if ($type === 'group') {
                $groupIds[] = $id;
                continue;
            }

            if ($type === 'buttonpoint' || $type === 'button_point') {
                $buttonPointIds[] = $id;
                continue;
            }

            return error('未知排序项类型', 400);
        }

        if (count(array_unique($groupIds)) !== count($groupIds) || count(array_unique($buttonPointIds)) !== count($buttonPointIds)) {
            return error('排序项重复', 400);
        }

        if (! empty($groupIds)) {
            $groupCount = Db::name('button_point_group')
                ->where('room_id', $roomId)
                ->whereIn('id', $groupIds)
                ->count();

            if ($groupCount !== count($groupIds)) {
                return error('分组数据不完整', 404);
            }
        }

        if (! empty($buttonPointIds)) {
            $buttonPoints = Db::name('button_point')
                ->where('room_id', $roomId)
                ->whereIn('id', $buttonPointIds)
                ->field('id,button_point_group_id')
                ->select()
                ->toArray();

            if (count($buttonPoints) !== count($buttonPointIds)) {
                return error('按钮点数据不完整', 404);
            }

            foreach ($buttonPoints as $buttonPoint) {
                if (! empty($buttonPoint['button_point_group_id'])) {
                    return error('仅支持未分组按钮点参与顶层排序', 400);
                }
            }
        }

        Db::startTrans();
        try {
            foreach ($entries as $index => $entry) {
                $type = strtolower((string) ($entry['type'] ?? ''));
                $id = (int) ($entry['id'] ?? 0);

                if ($type === 'group') {
                    Db::name('button_point_group')
                        ->where('id', $id)
                        ->where('room_id', $roomId)
                        ->update(['sort' => $index]);
                    continue;
                }

                Db::name('button_point')
                    ->where('id', $id)
                    ->where('room_id', $roomId)
                    ->update(['sort' => $index]);
            }

            Db::commit();

            return success(true, '顶层排序保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('顶层排序保存失败：' . $e->getMessage(), 500);
        }
    }
}