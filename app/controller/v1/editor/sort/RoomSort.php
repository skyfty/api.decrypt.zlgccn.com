<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class RoomSort 
{
    public function updateRoomSort(Request $request)
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
                // 用 index 作为新的顺序值（0、1、2...）
                Db::name('room')
                  ->where('id', $id)
                  ->update(['sort' => $index]);
            }
            Db::commit(); 
            return success(true, '排序成功');
        } catch (\Exception $e) {
            Db::rollback(); 
            return error('排序失败', 401); 
        }
    } 
     
}