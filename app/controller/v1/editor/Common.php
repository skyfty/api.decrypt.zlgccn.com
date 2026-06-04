<?php

namespace app\controller\v1\editor;

use think\facade\Request;
use think\facade\Db;

class Common
{
    // 白名单表（只让删这些）
    private $allowTable = [ 
        'image', // 图像资源
        'audio', // 音频资源
        'video', // 视频资源
        'animation', // 动画资源
        'item', // 物品资源
        'project', // 项目
        'city', // 城市
        'room', // 房间
        'button_point', // 按钮组
        'button_point_group', // 按钮分组
        'hint_point', // 提示
        'button_point_resources_image',  // ButtonPoint 私有资源
        'button_point_resources_audio', // ButtonPoint 私有资源
        'button_point_resources_animations', // ButtonPoint 私有资源
        'panel_transition_scene', // 转场
        'image_categories', // 图片分类
        'project_story_variables', 'room_story_variables',
        // 项目剧情
        'project_story_lines', 'project_story_line_conditions', 'project_story_line_actions',
        'translation_keyword',
        // tools
        'project_attribute',
        // UI
        'panel_attribute',
        'room_option_group'
    ];
    
    public function delete()
    {
        // 获取请求参数
        $id = Request::post('id');
        $table = Request::post('tableName');

        // 参数验证
        if (empty($id)) {
            return error('id 不能为空', 400);
        }

        if (empty($table)) {
            return error('table 不能为空', 400);
        }

        // 1. 表名合法性
        if (!in_array($table, $this->allowTable, true)) {
            return error('没有删除权限', 403);
        }

        // 2. 是否存在
        $row = Db::name($table)->find($id);
        if (!$row) {
            return error('记录不存在', 403);
        }

        // 3. 软删除 or 物理删除
        $fields = Db::getTableFields($table);
        if (in_array('delete_time', $fields, true)) {
            // 软删除
            Db::name($table)->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        } else {
            // 物理删除
            Db::name($table)->delete($id);
        }

        return success( null, '删除成功');
    }


}
