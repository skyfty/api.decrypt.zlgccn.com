<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;

class Setting
{
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
        $scale = (int) Request::param('scale', 100);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        $fieldsToExclude = [
            'background_image',
            'music_icon',
            'audio_icon',
            'close_icon',
            'submit_icon',
            'multiLanguage'
        ];

        // 1. 查询该项目的所有设置
        $settingItem = Db::table('panel_setting')
            ->where('project_id', $project_id)
            ->field($fieldsToExclude)
            ->find();

        // 2. 如果查询结果为空，则自动创建一条默认设置
        if (empty($settingItem)) {
            return error('设置获取失败，请确认是否配置', 500);
        } else {

            // 遍历查询结果
            $settingItem['background_image'] = Image::getImageUrlById($settingItem['background_image'], $scale);
            $settingItem['music_icon'] = Image::getImageUrlById($settingItem['music_icon'], $scale);
            $settingItem['audio_icon'] = Image::getImageUrlById($settingItem['audio_icon'], $scale);
            $settingItem['close_icon'] = Image::getImageUrlById($settingItem['close_icon'], $scale);
            $settingItem['submit_icon'] = Image::getImageUrlById($settingItem['submit_icon'], $scale);
            $settingItem['multiLanguage'] = (bool) $settingItem['multiLanguage'];

        }

        // 3. 返回标题数据
        return success($settingItem, empty($settingItem) ? '设置获取失败，请确认是否配置' : '设置获取成功');
    }
}
