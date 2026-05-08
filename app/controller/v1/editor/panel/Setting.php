<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;

class Setting
{

    private function getDefaultPanelSetting(int $project_id, int $image_id): array
    {
        return [
            'project_id'                        => $project_id,
            'background_image'                   => $image_id,
            'music_icon'                  => $image_id,
            'audio_icon'            => $image_id, 
            'audio_icon'            => $image_id, 
            'close_icon'            => $image_id, 
            'submit_icon'            => $image_id, 
            'create_time'    => date('Y-m-d H:i:s'),
            'update_time'    => date('Y-m-d H:i:s'),
        ];
    }
    /**
     * 获取指定项目的设置
     * GET /v1/editor/panel/GetSetting
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetSetting()
    {
        $project_id = (int) Request::param('project_id', 0);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 1. 查询该项目的所有标题
        $list = Db::table('panel_setting')
            ->where('project_id', $project_id)
            ->find();

        // 2. 如果查询结果为空，则自动创建一条默认标题
        if (empty($list)) {
            $imageRecord = Db::table('image')->where('projectId', $project_id)->find();

            $image_id = 0;

            if (!empty($imageRecord) && is_array($imageRecord)) {
                $image_id = $imageRecord['id'];
            } else {
                return error('图片资源为空，先配置图片资源', 500);
            }

            // 构造要插入的数据
            $row1 = $this->getDefaultPanelSetting($project_id, $image_id);

            // 插入数据并获取自增ID（可选，如果你需要记录ID）
            $insertId1 = Db::table('panel_setting')->insertGetId($row1);

            if ($insertId1 === false || $insertId1 <= 0) {
                return error('设置创建失败，请稍后重试', 500);
            }

            // 再次查询，获取刚刚插入的数据
            $list = Db::table('panel_setting')
                ->where('project_id', $project_id)
                ->find();
        }

        // 3. 返回标题数据
        return success($list, empty($list) ? '设置不存在已自动创建成功' : '设置获取成功');
    }

    /**
     * 上传/更新指定项目的设置
     * POST /v1/editor/panel/UploadSetting
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   project_id     int     必填    项目ID
     *   image_id       int     必填    图片ID
     *   background_id  int     必填    图片ID
     */
    public function UploadSetting()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $table = 'panel_setting';
        $id = (int) request()->post('id', 0);
        $project_id = (int) request()->post('project_id', 0);

        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }

        // ✅ 获取所有 POST 数据
        $postData = request()->post();

        // 如果是新增（id == 0），则添加 create_time
        $postData['update_time'] = date('Y-m-d H:i:s');
        $affected = Db::table($table)->where('id', $id)->update($postData);
        if ($affected === 0) {
            return error('未找到对应记录或数据未变更', 404);
        } else {
            return success($postData, '设置更新成功');
        }
    }
}
