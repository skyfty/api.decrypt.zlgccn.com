<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use think\facade\Validate;
use app\model\Image;
use app\model\Panel\update\PanelUpdate;

class Update
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

        $data = PanelUpdate::with(['PanelUpdateItems'])->where('project_id', $params['project_id'])->find();

        if(empty($data)) return success('更新 UI 尚未配置.');

        $data->width = $data->width * ($scale / 100);
        $data->height = $data->height * ($scale / 100);
        $data->background_url = Image::getImageUrlById($data->background_url, $scale);

        foreach ($data->PanelUpdateItems as &$PanelUpdateItem) {
            $PanelUpdateItem->width = $PanelUpdateItem->width * ($scale / 100);
            $PanelUpdateItem->height = $PanelUpdateItem->height * ($scale / 100);
            $PanelUpdateItem->x = $PanelUpdateItem->x * ($scale / 100);
            $PanelUpdateItem->y = $PanelUpdateItem->y * ($scale / 100);
            $PanelUpdateItem->background_url = Image::getImageUrlById($PanelUpdateItem->background_url, $scale);
        }

        return success($data);
    }
}
