<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Db;
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
        if (!$findUpdateData) {
            return error('记录不存在');
        }

        $items = $params['PanelUpdateItems'] ?? [];
        unset($params['PanelUpdateItems']);

        Db::startTrans();
        try {
            $updateState = $findUpdateData->save($params);
            if ($updateState !== false) {
                $keepIds = [];

                foreach ($items as $panelUpdateItem) {
                    $panelUpdateItem['panel_update_id'] = $findUpdateData->id;
                    $panelUpdateItem['update_time'] = date('Y-m-d H:i:s');

                    if (!empty($panelUpdateItem['id'])) {
                        $keepIds[] = (int) $panelUpdateItem['id'];
                        $findUpdateItemData = PanelUpdateItem::find($panelUpdateItem['id']);
                        if ($findUpdateItemData) {
                            $findUpdateItemData->save($panelUpdateItem);
                        }
                    } else {
                        $panelUpdateItem['create_time'] = date('Y-m-d H:i:s');
                        $newItem = new PanelUpdateItem();
                        $newItem->data($panelUpdateItem);
                        $newItem->save();
                        $keepIds[] = (int) $newItem->id;
                    }
                }

                if (!empty($keepIds)) {
                    PanelUpdateItem::where('panel_update_id', $findUpdateData->id)
                        ->whereNotIn('id', $keepIds)
                        ->delete();
                } else {
                    PanelUpdateItem::where('panel_update_id', $findUpdateData->id)->delete();
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
