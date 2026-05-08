<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use app\model\Image;
use think\facade\Validate;
use app\model\Panel\loading\PanelLoading;
use app\model\Panel\loading\PanelLoadingItem;

class Loading
{
    public function index()
    {
        $user = request()->user;
        if (empty($user)) {
            return error('用户不存在');
        }

        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $data = PanelLoading::with(['PanelLoadingItems'])->where('project_id', $params['project_id'])->find();

        if (empty($data)) {
            $findImage = Image::where('projectId', $params['project_id'])->field('id')->find();
            if (empty($findImage)) return error('图片资源不能为空.');
            $newPanelLoading = new PanelLoading();
            $newPanelLoading->project_id = $params['project_id'];
            $newPanelLoading->background_url = $findImage['id'];
            $createPanelLoading = $newPanelLoading->save();

            if($createPanelLoading) $data = PanelLoading::with(['PanelLoadingItems'])->where('project_id', $params['project_id'])->find();
            return success($data, '尚未配置，已自动创建成功.');
        }

        return success($data);
    }


    public function save()
    {
        $user = request()->user;
        if (empty($user)) {
            return error('用户不存在');
        }

        $params = request()->post();
        $findLoadingData = PanelLoading::find($params['id']);

        $LoadingState = $findLoadingData->save($params);
        if ($LoadingState) {
            foreach ($params['PanelLoadingItems'] as &$PanelLoadingItem) {

                $findLoadingItemData = PanelLoadingItem::find($PanelLoadingItem['id']);

                $LoadingItemState = $findLoadingItemData->save($PanelLoadingItem);
                $LoadingState = $LoadingItemState;
            }
        }
        if ($LoadingState) return success($LoadingState, '更新成功.');
        return error('更新失败.');
    }
}
