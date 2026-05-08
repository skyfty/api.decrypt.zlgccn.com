<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use app\model\Image;
use think\facade\Validate;
use app\model\Panel\update\PanelUpdate;
use app\model\Panel\update\PanelUpdateItem;

class Update
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

        $data = PanelUpdate::with(['PanelUpdateItems'])->where('project_id', $params['project_id'])->find();

        if (empty($data)) {
            $findImage = Image::where('projectId', $params['project_id'])->field('id')->find();
            if (empty($findImage)) return error('图片资源不能为空.');
            $newPanelUpdate = new PanelUpdate();
            $newPanelUpdate->project_id = $params['project_id'];
            $newPanelUpdate->background_url = $findImage['id'];
            $createPanelUpdate = $newPanelUpdate->save();

            if($createPanelUpdate) $data = PanelUpdate::with(['PanelUpdateItems'])->where('project_id', $params['project_id'])->find();
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
        $findUpdateData = PanelUpdate::find($params['id']);

        $updateState = $findUpdateData->save($params);
        if ($updateState) {
            foreach ($params['PanelUpdateItems'] as &$PanelUpdateItem) {

                $findUpdateItemData = PanelUpdateItem::find($PanelUpdateItem['id']);

                $updateItemState = $findUpdateItemData->save($PanelUpdateItem);
                $updateState = $updateItemState;
            }
        }
        if ($updateState) return success($updateState, '更新成功.');
        return error('更新失败.');
    }
}
