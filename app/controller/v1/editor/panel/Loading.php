<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Db;
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
        if (!$findLoadingData) {
            return error('记录不存在');
        }

        $items = $params['PanelLoadingItems'] ?? [];
        unset($params['PanelLoadingItems']);

        Db::startTrans();
        try {
            $loadingState = $findLoadingData->save($params);
            if ($loadingState !== false) {
                $keepIds = [];

                foreach ($items as $panelLoadingItem) {
                    $panelLoadingItem['panel_loading_id'] = $findLoadingData->id;
                    $panelLoadingItem['update_time'] = date('Y-m-d H:i:s');

                    if (!empty($panelLoadingItem['id'])) {
                        $keepIds[] = (int) $panelLoadingItem['id'];
                        $findLoadingItemData = PanelLoadingItem::find($panelLoadingItem['id']);
                        if ($findLoadingItemData) {
                            $findLoadingItemData->save($panelLoadingItem);
                        }
                    } else {
                        $panelLoadingItem['create_time'] = date('Y-m-d H:i:s');
                        $newItem = new PanelLoadingItem();
                        $newItem->data($panelLoadingItem);
                        $newItem->save();
                        $keepIds[] = (int) $newItem->id;
                    }
                }

                if (!empty($keepIds)) {
                    PanelLoadingItem::where('panel_loading_id', $findLoadingData->id)
                        ->whereNotIn('id', $keepIds)
                        ->delete();
                } else {
                    PanelLoadingItem::where('panel_loading_id', $findLoadingData->id)->delete();
                }

                Db::commit();
                return success(true, '更新成功.');
            }

            Db::rollback();
            return error('更新失败.');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('更新失败：' . $e->getMessage());
        }
    }
}
