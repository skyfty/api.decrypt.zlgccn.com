<?php
declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

use think\facade\Request;
use think\facade\Db;

class Resources
{
    /**
     * 获取项目资源列表
     * GET /api/v1/GlobalResources/Resources
     *
     * 查询参数：
     *   type        string  必选  image|audio|video|animation|model
     *   projectId   string  必选  项目ID
     */
    public function GetResources()
    {
        // 取 GET 参数
        $type = Request::param('type', '');
        $projectId = Request::param('project_id', '');
        $scale = Request::param('scale', 0);
        
        if ($type === '') {
            return error('类型不能为空', 400);
        }
        
        if ($projectId === '') {
            return error('项目ID不能为空', 400);
        }
    
        // 白名单校验
        $allowTypes = ['image', 'audio', 'video', 'animation', 'model'];
        if (!in_array($type, $allowTypes, true)) {
            return error('类型不合法', 400);
        }
    
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$type}'");
        
        if (empty($exists)) {
            return error("资源类型不存在", 404);
        }

        // 只选择需要的字段
        $fields = ['id', 'name', 'file' ];

        $list = Db::name($type)
                ->where('projectId', $projectId)
                ->field($fields)  // 重点：限制返回字段
                ->select()
                ->toArray();
        
        // 处理图片资源的缩略图
        if ( $list && $type === 'image' && $scale) {
            foreach ($list as &$item) {
                $item['file'] = $item['file'] . "?scale={$scale}";
            }
            unset($item);
        }
    
        return success($list, '资源获取成功');
    }
}