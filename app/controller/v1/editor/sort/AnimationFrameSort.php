<?php
declare(strict_types=1);

namespace app\controller\v1\editor\sort;

use think\facade\Request;
use think\facade\Db;

class AnimationFrameSort 
{
    public function updateAnimationFrameSort()
    {
        // 获取前端传来的 id 数组 
        $ids = request()->post('ids/a');
        $ids             = Request::post('ids/a', []);
        if (empty($ids) || !is_array($ids)) {
            return error('参数错误', 401);  
        }

        // 启动事务，批量更新 sort 字段
        Db::startTrans();
        try {
            foreach ($ids as $index => $id) {
                Db::name('animation_frames')
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