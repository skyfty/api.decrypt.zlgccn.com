<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Db;
use think\facade\Validate;

class CitySort
{
    public function updateCitySort()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'project_id' => 'require|number',
            'ids' => 'require',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $projectId = (int) $params['project_id'];
        $ids = array_values(array_filter(array_map('intval', (array) request()->post('ids/a', []))));

        if (empty($ids)) {
            return error('参数错误', 400);
        }

        if (count(array_unique($ids)) !== count($ids)) {
            return error('排序项重复', 400);
        }

        $cityCount = Db::name('city')
            ->where('project_id', $projectId)
            ->whereIn('id', $ids)
            ->count();

        if ($cityCount !== count($ids)) {
            return error('城市数据不完整', 404);
        }

        Db::startTrans();
        try {
            foreach ($ids as $index => $id) {
                Db::name('city')
                    ->where('id', $id)
                    ->where('project_id', $projectId)
                    ->update(['sort' => $index]);
            }

            Db::commit();

            return success(true, '城市排序成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('城市排序失败：' . $e->getMessage(), 500);
        }
    }
}