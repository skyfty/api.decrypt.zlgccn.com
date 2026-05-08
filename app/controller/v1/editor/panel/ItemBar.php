<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;
use think\facade\Validate;
use app\model\Panel\itemBar\PanelItemBar;
use app\model\Panel\itemBar\PanelItemBarItemSlot;

class ItemBar
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $param = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        if (!$validate->check($param)) {
            return error($validate->getError(), 400);
        }
        $projectId = $param['project_id'];

        try {
            $ItemBarData = PanelItemBar::where('project_id', $projectId)->find();

            // 如果没有数据误
            if (!$ItemBarData || $ItemBarData->isEmpty()) {
                // 假设你要检查项目中是否存在图片
                $image = Image::where('projectId', $projectId)->find();
                if (!$image) {
                    return error('图片资源为空，先配置图片资源');
                }
                $param['project_id'] = $projectId;
                $newPanelItemBar = new PanelItemBar();
                $newPanelItemBar->project_id = $projectId;
                $newPanelItemBar->background_image = $image->id;
                $newPanelItemBar->save();
                $newPanelItemBarItemSlot = new PanelItemBarItemSlot();
                $newPanelItemBarItemSlot->project_id = $projectId;
                $newPanelItemBarItemSlot->background_image = $image->id;
                $newPanelItemBarItemSlot->save();
            }

            $findnewPanelItemBar = PanelItemBar::with('ItemBarItemSlot')->where('project_id', $projectId)->find();
            if ($findnewPanelItemBar) {
                return success($findnewPanelItemBar, '该项目尚未配置，已自动创建默认配置.');
            } else {
                return error('该项目尚未配置，自动创建默认配置失败.');
            }
            return success($ItemBarData);
        } catch (\Exception $e) {
            return error('更新失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 保存更新游戏数据
     */
    public function save()
    {
        $param = request()->post();
        $validate = Validate::rule([
            'id' => 'require'
        ]);
        if (!$validate->check($param)) {
            return error($validate->getError(), 400);
        }


        try {
            Db::startTrans(); // 开启事件
            $findItemBar = PanelItemBar::find($param['id']);
            $findItemBar->width = $param['width'];
            $findItemBar->height = $param['height'];
            $findItemBar->x = $param['x'];
            $findItemBar->y = $param['y'];
            $findItemBar->background_image = $param['background_image'];
            $findItemBar->save();
            
            $findItemBarItemSlot = PanelItemBarItemSlot::find($param['ItemBarItemSlot']['id']);
            $findItemBarItemSlot->width = $param['ItemBarItemSlot']['width'];
            $findItemBarItemSlot->height = $param['ItemBarItemSlot']['height'];
            $findItemBarItemSlot->x = $param['ItemBarItemSlot']['x'];
            $findItemBarItemSlot->y = $param['ItemBarItemSlot']['y'];
            $findItemBarItemSlot->background_image = $param['ItemBarItemSlot']['background_image'];
            $findItemBarItemSlot->save();

            Db::commit(); // 事件完成

            if ($findItemBar) {
                return success($findItemBar, '更新成功');
            } else {
                return error('更新失败', 500);
            }
        } catch (\Exception $e) {
            Db::rollback(); // 回退
            return error('更新失败：' . $e->getMessage(), 500);
        }
    }
}
