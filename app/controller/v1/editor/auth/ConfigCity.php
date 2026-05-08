<?php
declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\exception\ValidateException;
use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;

class ConfigCity
{
    /**
     * 新建或更新城市
     */
    public function saveCity()
    {
        // 获取当前登录用户信息
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        
        // 获取参数
        $id = Request::post('id/d', 0); // 转为整数，默认0表示新增
        $project_id = Request::post('project_id/d');
        $name = Request::post('name/s');
        
        // 验证请求参数
        if (empty($project_id)) {
            return error('城市ID不能为空', 400);
        }
        
        $project = Db::name('project')->find($project_id);
        if (empty($project)) {
            return error('项目不存在', 403);
        }

        if (empty($name)) {
            return error('城市名称不能为空', 400);
        }

        
        // 验证权限
        if ($id > 0) {
            $city = Db::name('city')->find($id);
            if (empty($city)) {
                return error('无权操作该城市', 403);
            }
        }
        
        try {
            Db::startTrans();
            
            if ($id > 0) {
                // 更新城市
                $result = Db::name('city')
                    ->where('id', $id)
                    ->update([
                        'name' => $name,
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                
                if ($result === false) {
                    throw new \Exception('城市更新失败');
                }
                $projectId = $id;
            } else {
                // 新增城市
                $projectId = Db::name('city')->insertGetId([
                    'project_id' => $project_id,
                    'name' => $name,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                
                if ($projectId <= 0) {
                    throw new \Exception('城市创建失败');
                }
            }
            
            Db::commit();
            return success([
                'id' => $projectId,
                'message' => $id > 0 ? '城市更新成功' : '城市创建成功'
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 500);
        }
    }
    
}