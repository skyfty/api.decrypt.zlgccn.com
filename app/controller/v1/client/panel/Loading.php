<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Validate;
use app\model\Image;
use app\model\Panel\loading\PanelLoading;

class Loading
{
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);

        $scale = $params['scale'] ?? 100;


        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $data = PanelLoading::with(['PanelLoadingItems'])->where('project_id', $params['project_id'])->find();

        if(empty($data)) return success('载入 UI 尚未配置.');

        $data->width = $data->width * ($scale / 100);
        $data->height = $data->height * ($scale / 100);
        $data->background_url = Image::getImageUrlById($data->background_url, $scale);

        foreach ($data->PanelLoadingItems as &$PanelLoadingItem) {
            $PanelLoadingItem->width = $PanelLoadingItem->width * ($scale / 100);
            $PanelLoadingItem->height = $PanelLoadingItem->height * ($scale / 100);
            $PanelLoadingItem->x = $PanelLoadingItem->x * ($scale / 100);
            $PanelLoadingItem->y = $PanelLoadingItem->y * ($scale / 100);
            $PanelLoadingItem->background_url = Image::getImageUrlById($PanelLoadingItem->background_url, $scale);
        }

        return success($data);
    }
}
