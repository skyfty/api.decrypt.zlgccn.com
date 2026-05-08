<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class HintPointSort
{
    public function updateHintPointSort(Request $request)
    {
        // 获取前端传来的 id 数组 
        $ids = request()->post('ids/a');
        if (empty($ids) || !is_array($ids)) {
            return error('参数错误', 401);  
        }

        // 启动事务，批量更新 sort 字段
        Db::startTrans();
        try {
            foreach ($ids as $index => $id) {  
                Db::table('hint_point') 
                  ->where('id', $id)
                  ->update(['sort' => $index]);
            }
            Db::commit(); 
            return success('HintPoint 排序成功', 'HintPoint 排序成功');
        } catch (\Exception $e) {
            Db::rollback(); 
            trace('HintPoint 排序失败：' . $e->getMessage(), 'error');
            return error('排序失败：' . $e->getMessage(), 500);
        }
    } 
     
}