<?php

declare(strict_types=1);

namespace app\controller\v1\client\projectRelease;

use think\facade\Request;
use think\facade\Db;

class IOSPublishMaterial
{
    /**
     * 获取指定项目的IOS发行材料
     * GET /v1/client/projectRelease/GetIOSPublishMaterials
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetIOSPublishMaterials()
    {
        $project_id = (int) Request::param('project_id', 0);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 查询数据
        $list = Db::table('IOS_materials')
            ->where('project_id', $project_id)
            ->withoutField(['project_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        // 先初始化分组，确保 iPhone 和 iPad 都存在，哪怕没有数据
        $groupedData = [
            'iPhone' => [],
            'iPad'   => [],
        ];

        // 遍历查询结果，填充到对应分组
        foreach ($list as $item) {
            $specType = $item['spec_type'];
            if (isset($groupedData[$specType])) {
                $groupedData[$specType][] = $item;
            }
        }

        // 返回分组后的数据（现在 iPhone 和 iPad 一定存在，可能为空数组）
        return success($groupedData, '获取成功');
    }






}
