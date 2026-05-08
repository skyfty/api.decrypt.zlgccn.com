<?php
declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class SafeZoneResources
{
    /**
     * 获取当前用户的安全区资源列表
     */
    public function GetSafeZoneResources()
    {
        $projectId = Request::get('project_id', '');
        if (empty($projectId)) {
            return error('项目ID不能为空', 400);
        }
    
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE 'SafeZone'");
        
        if (empty($exists)) {
            return error("安全区资源表不存在", 404);
        }
    
        $list = Db::table('SafeZone')
                    ->where('projectId', $projectId)
                //   ->order('id', 'asc')
                    ->withoutField(['projectId', 'create_time', 'update_time'])
                    ->select()
                    ->toArray();
    
        return success($list, '安全区资源获取成功');
    }
    
}