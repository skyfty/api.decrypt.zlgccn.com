<?php
declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

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
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $projectId = Request::get('projectId', '');
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
                //   ->order('name', 'asc')
                  ->select()
                  ->toArray();
    
        return success($list, '安全区资源获取成功');
    }
    
    /**
     * 更新安全区资源
     * POST /api/v1/GlobalResources/UploadSafeZoneResources
     *
     * 表单字段：
     *   id        int     可选  传 0 或留空=新增；传已有 id=更新
     */
    public function UploadSafeZoneResources()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE 'SafeZone'");
        if (empty($exists)) {
            return error("安全区资源表不存在", 404);
        }

        $id = (int)Request::post('id', 0);
        $projectId = Request::post('projectId', '');

        if (empty($projectId)) {
            return error('项目ID不能为空', 400);
        }

        $data = [
            'projectId'   => $projectId,
            'name'        => Request::post('name', '未命名'),
            'width'       => Request::post('width', 200),
            'height'      => Request::post('height', 200),
            'x'           => Request::post('x', 0),
            'y'           => Request::post('y', 0),
            'update_time'=> date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 更新
            $result = Db::table('SafeZone')
                       ->where('id', $id)
                       ->where('projectId', $projectId)
                       ->update($data);
            if ($result === false) {
                return error('安全区更新失败', 500);
            }
            return success(['id' => $id], '安全区更新成功');
        } else {
            // 新增
            $data['create_time'] = date('Y-m-d H:i:s');
            $newId = Db::table('SafeZone')->insertGetId($data);
            if (!$newId) {
                return error('安全区添加失败', 500);
            }
            return success(['id' => $newId], '安全区添加成功');
        }
    }
}