<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use app\model\Image;
use app\model\Panel\hint\PanelHint;

class Hint
{
    /**
     * 获取指定项目的设置
     * GET /v1/editor/panel/GetSetting
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetHint()
    {
        $project_id = (int) Request::param('project_id', 0);
        $scale = (int) Request::param('scale', 100);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        $fieldsToExclude = [
            'title_background_image',
            'background_image',
            'close_icon',
        ];

        // 1. 查询该项目的所有设置
        $settingItem = PanelHint::where('project_id', $project_id)
            ->field($fieldsToExclude)
            ->find();

        // 2. 如果查询结果为空，则自动创建一条默认设置
        if (empty($settingItem)) {
            return error('获取失败，请确认是否配置', 500);
        } else {
            // 遍历查询结果
            $settingItem['title_background_image'] = Image::getImageUrlById($settingItem['title_background_image'], $scale);
            $settingItem['background_image'] = Image::getImageUrlById($settingItem['background_image'], $scale);
            $settingItem['close_icon'] = Image::getImageUrlById($settingItem['close_icon'], $scale);

        }

        // 3. 返回标题数据
        return success($settingItem, empty($settingItem) ? '获取失败，请确认是否配置' : '获取成功');
    }
}
