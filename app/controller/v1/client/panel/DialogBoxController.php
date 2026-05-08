<?php

declare(strict_types=1);

namespace app\controller\v1\client\panel;

use app\model\Project;
use app\model\Image;
use think\facade\Validate;
use app\model\Panel\dialogBox\DialogBox;

class DialogBoxController
{
    private $scale = 100;
    public function index()
    {

        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $this->scale = $params['scale'] ?? 100;

        try {
            $findProject = Project::find($params['project_id']);
            if (empty($findProject)) return error('项目不存在', 400);

            $data = DialogBox::with(['dialogBoxOption', 'dialogBoxRole', 'dialogBoxRoleName'])->where('project_id', $params['project_id'])->find();

            if (empty($data)) return error('对话框未配置', 400);

            $formattedData = $this->formattedDialogBoxData($data);
            return success($formattedData);
        } catch (\Exception $e) {
            return error('查新异常：', $e->getMessage());
        }
    }

    public function formattedDialogBoxData($data)
    {
        $formattedData = [
            'dialogBox' => [
                'id' => $data->id,
                'project_id' => $data->project_id,
                'width' => $data->width * $this->scaleRatio(),
                'height' => $data->height * $this->scaleRatio(),
                'x' => $data->x * $this->scaleRatio(),
                'y' => $data->y * $this->scaleRatio(),
                'background_image' => Image::getImageUrlById($data->background_image, $this->scale),
                'z_index' => $data->z_index,
                'border' => [
                    'width' => $data->border_width * $this->scaleRatio(),
                    'radius' => $data->border_radius * $this->scaleRatio(),
                    'color' => $data->border_color,
                    'style' => $data->border_style
                ],
                'text_area' => [
                    'width' => $data->text_area_width * $this->scaleRatio(),
                    'height' => $data->text_area_height * $this->scaleRatio(),
                    'x' => $data->text_area_x * $this->scaleRatio(),
                    'y' => $data->text_area_y * $this->scaleRatio(),
                    'color' => $data->text_area_color,
                    'font_size' => $data->text_area_size * $this->scaleRatio(),
                ]
            ],
            'dialogBoxOption' => [
                'id' => $data->dialogBoxOption->id,
                'width' => $data->dialogBoxOption->width * $this->scaleRatio(),
                'height' => $data->dialogBoxOption->height * $this->scaleRatio(),
                'x' => $data->dialogBoxOption->x * $this->scaleRatio(),
                'y' => $data->dialogBoxOption->y * $this->scaleRatio(),
                'background_image' => Image::getImageUrlById($data->dialogBoxOption->background_image, $this->scale),
                'z_index' => $data->dialogBoxOption->z_index,
                'border' => [
                    'width' => $data->dialogBoxOption->border_width * $this->scaleRatio(),
                    'radius' => $data->dialogBoxOption->border_radius * $this->scaleRatio(),
                    'color' => $data->dialogBoxOption->border_color,
                    'style' => $data->dialogBoxOption->border_style,
                ],
                'item' => [
                    'width' => $data->dialogBoxOptionItem->width * $this->scaleRatio(),
                    'height' => $data->dialogBoxOptionItem->height * $this->scaleRatio(),
                    'background_image' => Image::getImageUrlById($data->dialogBoxOptionItem->background_image, $this->scale),
                    'color' => $data->dialogBoxOptionItem->color,
                    'font_size' => $data->dialogBoxOptionItem->font_size * $this->scaleRatio(),
                    'z_index' => $data->dialogBoxOptionItem->z_index,
                ],
            ],
            'dialogBoxRole' => [
                'id' => $data->dialogBoxRole->id,
                'width' => $data->dialogBoxRole->width * $this->scaleRatio(),
                'height' => $data->dialogBoxRole->height * $this->scaleRatio(),
                'x' => $data->dialogBoxRole->x * $this->scaleRatio(),
                'y' => $data->dialogBoxRole->y * $this->scaleRatio(),
                'background_image' => Image::getImageUrlById($data->dialogBoxRole->role, $this->scale),
                'z_index' => $data->dialogBoxRole->z_index,
                'roleName' => [
                    'id' => $data->dialogBoxRoleName->id,
                    'width' => $data->dialogBoxRoleName->width * $this->scaleRatio(),
                    'height' => $data->dialogBoxRoleName->height * $this->scaleRatio(),
                    'x' => $data->dialogBoxRoleName->x * $this->scaleRatio(),
                    'y' => $data->dialogBoxRoleName->y * $this->scaleRatio(),
                    'font_size' => $data->dialogBoxRoleName->font_size * $this->scaleRatio(),
                    'color' => $data->dialogBoxRoleName->color,
                    'z_index' => $data->dialogBoxRoleName->z_index,
                ]
            ]
        ];

        return $formattedData;
    }

    private function scaleRatio()
    {
        return $this->scale / 100;
    }
}
