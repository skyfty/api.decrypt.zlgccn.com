<?php
namespace app\controller\v1\editor\buttonPoint;

use think\facade\Request;
use think\facade\Db;
use app\support\ButtonPointBuilder;

class ButtonPointResources
{
    public function GetButtonPointResources()
    {
        // 从请求参数中获取 projectId，必传
        $buttonPoint_id = (int)Request::param('buttonPoint_id', 0);
        if ($buttonPoint_id === 0) {
            return error('ButtonPointId不能为空或无效', 400);
        }

        // 白名单校验：资源类型
        $tableName = Request::param('tableName', '');
        if ((empty($tableName))) {
            return error('查询表名不能为空', 400);
        }

        // 白名单：只允许 'item' 类型
        $allowTypes = [
            'button_point_resources_image', 
            'button_point_resources_animations',
            'button_point_resources_video',
            'button_point_resources_audio'
        ];

        if (!in_array($tableName, $allowTypes, true)) {
            return error('未有该操作权限', 400);
        }

        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$tableName}'");
        if (empty($exists)) {
            return error("该查询表不存在", 404);
        }

        // 查询该 projectId 下的资源列表
        $list = Db::name($tableName)
            ->where('buttonPoint_id', $buttonPoint_id)
            ->field(['id', 'buttonPoint_id', 'resource_id'])
            ->select()
            ->toArray();

        return success($list, '获取成功');

    }


    public function UpdateButtonPointResource()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id     = (int)Request::post('id', 0);
        $buttonPoint_id     = (int)Request::post('buttonPoint_id', 0);
        $tableName   = Request::post('tableName');

        if (empty($id)) {
            if ($buttonPoint_id === 0) {
                return error('buttonPoint_id 不能为空', 401);
            }
        }
        
        if (empty($tableName)) {
            return error('tableName 不能为空', 401);
        }

        $data = [
            'resource_id'     => (int)Request::post('resource_id', 0),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        Db::startTrans();
        try {
            if ($id === 0) { 
                // 新增
                $data['buttonPoint_id'] = $buttonPoint_id;
                $data['create_time'] = date('Y-m-d H:i:s');
                Db::table($tableName)->insertGetId($data); 
            } else {
                // 更新 
                Db::table($tableName)->where('id', $id)->update($data);
            }
            Db::commit();
            return success(true, $id ? '同步成功' : '新建成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error($id ? '同步失败' . $e->getMessage() : '新建失败' . $e->getMessage(), 500);
        }
    }
}