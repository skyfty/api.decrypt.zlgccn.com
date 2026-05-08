<?php
declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\exception\ValidateException;
use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;

class ConfigProject
{
    /**
     * 新建或更新项目
     */
    public function saveProject()
    {
        // 获取当前登录用户信息
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        
        // 验证请求参数
        try {
            $this->validateParams();
        } catch (ValidateException $e) {
            return error($e->getMessage(), 400);
        }
        
        // 获取参数
        $id = Request::post('id/d', 0); // 转为整数，默认0表示新增
        $userId = Request::post('user_id/d');
        $name = Request::post('name/s');
        
        // 验证用户权限，确保只能操作自己的项目
        if ($id > 0) {
            $project = Db::name('project')->find($id);
            if (empty($project) || $project['user_id'] != $user['id']) {
                return error('无权操作该项目', 403);
            }
        }
        
        try {
            Db::startTrans();
            
            if ($id > 0) {
                // 更新项目
                $result = Db::name('project')
                    ->where('id', $id)
                    ->update([
                        'name' => $name,
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                
                if ($result === false) {
                    throw new \Exception('项目更新失败');
                }
                $projectId = $id;
            } else {
                // 新增项目
                $projectId = Db::name('project')->insertGetId([
                    'user_id' => $userId,
                    'name' => $name,
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                
                if ($projectId <= 0) {
                    throw new \Exception('项目创建失败');
                }
            }
            
            Db::commit();
            return success([
                'id' => $projectId,
                'message' => $id > 0 ? '项目更新成功' : '项目创建成功'
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 500);
        }
    }
    
    /**
     * 验证请求参数
     */
    private function validateParams()
    {
        $rule = [
            'user_id' => 'require|integer|gt:0',
            'name' => 'require|trim|max:100',
            'id' => 'integer|egt:0'
        ];
        
        $message = [
            'user_id.require' => '用户ID不能为空',
            'user_id.integer' => '用户ID必须为整数',
            'user_id.gt' => '用户ID必须大于0',
            'name.require' => '项目名称不能为空',
            'name.max' => '项目名称不能超过100个字符',
            'id.integer' => '项目ID必须为整数',
            'id.egt' => '项目ID不能为负数'
        ];
        
        Validate::rule($rule, $message)->check(Request::post());
    }
}