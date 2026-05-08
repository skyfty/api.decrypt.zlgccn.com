<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;
use app\model\Panel\hint\PanelHint;

class Hint
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $projectId = Request::param('project_id');


        $HintData = PanelHint::where('project_id', $projectId)->find();

        // 如果没有数据误
        if (!$HintData) {
            // 假设你要检查项目中是否存在图片
            $image = Image::where('projectId', $projectId)->find();
            if (!$image) {
                return error('图片资源为空，先配置图片资源');
            }
            $param['project_id'] = $projectId;
            $param['title_background_image'] = $image['id'];
            $param['background_image'] = $image['id'];
            $param['close_icon'] = $image['id'];
            $createResult = $this->createHint($param);

            if ($createResult) {
                return success($createResult, '该项目尚未配置，已自动创建默认配置.');
            } else {
                return error('该项目尚未配置，自动创建默认配置失败.');
            }
        }

        // 获取本地化文本
        $HintData = PanelHint::where('project_id', $projectId)->find();

        return success($HintData);
    }

    /**
     * 保存更新游戏数据
     */
    public function save()
    {
        $param = Request::post();

        if (empty($param['project_id'])) {
            return error('关联项目ID 不能为空.');
        }

        try {
            Db::startTrans();

            if (!empty($param['id'])) {
                // 更新操作
                $result = $this->updateHint($param);
            } else {
                // 创建操作
                $result = $this->createHint($param);        
            }

            Db::commit();

            if ($result) {
                $message = !empty($param['id']) ? '更新成功' : '创建成功';
                return success($result, $message);
            } else {
                return error('操作失败', 500);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return error('操作失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新游戏数据及对应的本地化文本
     */
    private function updateHint(array $param)
    {
        $Hint = PanelHint::find($param['id']);
        if (!$Hint) {
            return error('记录不存在');
        }

        $param['update_time'] = date('Y-m-d H:i:s');
        $HintSaveResult = $Hint->save($param);

        return $HintSaveResult;
    }


    /**
     * 创建游戏数据及对应的本地化文本
     */
    private function createHint(array $param)
    {
        // 第一条记录：type为hint
        $hint = new PanelHint;
        $hint->project_id = $param['project_id'];
        $hint->title_background_image = $param['title_background_image'];
        $hint->background_image = $param['background_image'];
        $hint->close_icon = $param['close_icon'];
        $hint->save();

        // 返回创建的游戏数组
        return $hint;
    }


}
