<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use app\model\Image;
use think\facade\Validate;
use app\model\Panel\dialogBox\DialogBox;
use app\model\Panel\dialogBox\DialogBoxOption;
use app\model\Panel\dialogBox\DialogBoxOptionItem;
use app\model\Panel\dialogBox\DialogBoxRole;
use app\model\Panel\dialogBox\DialogBoxRoleName;

class DialogBoxController
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
        try {

            $data = DialogBox::with(['dialogBoxOption', 'dialogBoxOptionItem', 'dialogBoxRole', 'dialogBoxRoleName'])->where('project_id', $params['project_id'])->find();
 
            if (empty($data)) {
                // 查询项目是否存在图片
                $findImage = Image::where('projectId', $params['project_id'])->find();

                if (empty($findImage)) return error('对话框未配置，且项目暂无图片，请上传图片后重试');

                // 创建默认配置
                $this->createDefaultDialogBoxConfig($params['project_id'], $findImage['id']);

                $data = DialogBox::with(['dialogBoxOption', 'dialogBoxRole', 'dialogBoxRoleName'])->where('project_id', $params['project_id'])->find();
            }

            $formattedData = $this->formattedDialogBoxData($data);
            return success($formattedData);
        } catch (\Exception $e) {
            // 出现异常时回滚事务
            \think\facade\Db::rollback();
            return error('查新异常：', $e->getMessage());
        }
    }

    private function createDefaultDialogBoxConfig($projectId, $findImageId)
    {
        // 创建并保存DialogBox及其关联的Option和Role
        $newDialogBox = new DialogBox(['project_id' => $projectId, 'background_image' => $findImageId]);
        $newDialogBoxOption = new DialogBoxOption(['project_id' => $projectId, 'background_image' => $findImageId]);
        $newDialogBoxOptionItem = new DialogBoxOptionItem(['project_id' => $projectId, 'background_image' => $findImageId]);
        $newDialogBoxRole = new DialogBoxRole(['project_id' => $projectId, 'role' => $findImageId]);
        $newDialogBoxRoleName = new DialogBoxRoleName(['project_id' => $projectId, 'role' => $findImageId]);

        $newDialogBox->save();
        $newDialogBoxOption->save();
        $newDialogBoxOptionItem->save();
        $newDialogBoxRole->save();
        $newDialogBoxRoleName->save();
    }

    public function formattedDialogBoxData($data)
    {
        $formattedData = [
            'id' => $data->id,
            'project_id' => $data->project_id,
            'width' => $data->width,
            'height' => $data->height,
            'x' => $data->x,
            'y' => $data->y,
            'background_image' => $data->background_image,
            'z_index' => $data->z_index,
            'border_radius' => $data->border_radius,
            'border_color' => $data->border_color,
            'border_width' => $data->border_width,
            'border_style' => $data->border_style,
            'text_area_width' => $data->text_area_width,
            'text_area_height' => $data->text_area_height,
            'text_area_x' => $data->text_area_x,
            'text_area_y' => $data->text_area_y,
            'text_area_color' => $data->text_area_color,
            'text_area_size' => $data->text_area_size,
            'dialogBoxOption' => [
                'id' => $data->dialogBoxOption->id,
                'width' => $data->dialogBoxOption->width,
                'height' => $data->dialogBoxOption->height,
                'x' => $data->dialogBoxOption->x,
                'y' => $data->dialogBoxOption->y,
                'border_radius' => $data->dialogBoxOption->border_radius,
                'border_color' => $data->dialogBoxOption->border_color,
                'border_width' => $data->dialogBoxOption->border_width,
                'border_style' => $data->dialogBoxOption->border_style,
                'background_image' => $data->dialogBoxOption->background_image,
                'z_index' => $data->dialogBoxOption->z_index,
                'item' => [
                    'id' => $data->dialogBoxOptionItem->id,
                    'width' => $data->dialogBoxOptionItem->width,
                    'height' => $data->dialogBoxOptionItem->height,
                    'background_image' => $data->dialogBoxOptionItem->background_image,
                    'color' => $data->dialogBoxOptionItem->color,
                    'font_size' => $data->dialogBoxOptionItem->font_size,
                    'z_index' => $data->dialogBoxOptionItem->z_index,
                ],
            ],
            'dialogBoxRole' => [
                'id' => $data->dialogBoxRole->id,
                'width' => $data->dialogBoxRole->width,
                'height' => $data->dialogBoxRole->height,
                'x' => $data->dialogBoxRole->x,
                'y' => $data->dialogBoxRole->y,
                'role' => $data->dialogBoxRole->role,
                'z_index' => $data->dialogBoxRole->z_index,
            ],
            'dialogBoxRoleName' => [
                'id' => $data->dialogBoxRoleName->id,
                'name' => $data->dialogBoxRoleName->name,
                'width' => $data->dialogBoxRoleName->width,
                'height' => $data->dialogBoxRoleName->height,
                'x' => $data->dialogBoxRoleName->x,
                'y' => $data->dialogBoxRoleName->y,
                'font_size' => $data->dialogBoxRoleName->font_size,
                'color' => $data->dialogBoxRoleName->color,
                'z_index' => $data->dialogBoxRoleName->z_index,
            ]
        ];

        return $formattedData;
    }

    public function save()
    {
        $user = request()->user;
        if (empty($user)) {
            return error('用户不存在');
        }

        $params = request()->post();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        try {
            $findDialogBox = DialogBox::find($params['id']);
            if (empty($findDialogBox)) return error('对话框配置不存在');
            $findDialogBox->save($params);

            $findDialogBoxOption = DialogBoxOption::find($params['dialogBoxOption']['id']);
            if (empty($findDialogBoxOption)) return error('对话框配置不存在');
            $findDialogBoxOption->save($params['dialogBoxOption']);
            
            $findDialogBoxOptionItem = DialogBoxOptionItem::find($params['dialogBoxOption']['item']['id']);
            if (empty($findDialogBoxOptionItem)) return error('对话框配置不存在');
            $findDialogBoxOptionItem->save($params['dialogBoxOption']['item']);


            $DialogBoxRole = DialogBoxRole::find($params['dialogBoxRole']['id']);
            if (empty($DialogBoxRole)) return error('对话框配置不存在');
            $DialogBoxRole->save($params['dialogBoxRole']);

            $DialogBoxRoleName = DialogBoxRoleName::find($params['dialogBoxRoleName']['id']);
            if (empty($DialogBoxRoleName)) return error('对话框配置不存在');
            $DialogBoxRoleName->save($params['dialogBoxRoleName']);

            return success($params, '保存成功.');
        } catch (\Exception $e) {
            return error('保存异常：', $e->getMessage());
        }
    }
}
