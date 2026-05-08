<?php

namespace app\controller\v1\editor\globalResources;

use app\BaseController;
use think\facade\Validate;
use app\model\globalConfig\ImageCategory;
use app\model\Image;

class image_categories extends BaseController
{
    /**
     * 获取图片分类列表
     */
    public function index()
    {
        try {
            $projectId = $this->request->param('project_id');

            if (!$projectId) {
                return json(['code' => 400, 'msg' => '项目ID不能为空']);
            }

            $categories = ImageCategory::byProject($projectId)->select();

            return json([
                'code' => 200,
                'data' => $categories,
                'msg' => '获取成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '服务器错误：' . $e->getMessage()
            ]);
        }
    }
    /**
     * 创建新的图片分类
     */
    public function create()
    {
        try {
            $data = $this->request->post();

            // 验证数据
            $validate = Validate::rule([
                'project_id' => 'require|number',
                'type' => 'require',
            ]);

            if (!$validate->check($data)) {
                return json(['code' => 400, 'msg' => $validate->getError()]);
            }

            // 检查分类名称是否已存在
            $exists = ImageCategory::where('project_id', $data['project_id'])
                ->where('type', $data['type'])
                ->find();

            if ($exists) {
                return json(['code' => 400, 'msg' => '该分组名称已存在']);
            }

            // 创建分类
            $category = ImageCategory::create([
                'project_id' => $data['project_id'],
                'type' => $data['type']
            ]);

            return success($category, '分组创建成功');
        } catch (\Exception $e) {
            return error('服务器错误：' . $e->getMessage(), 500);
        }
    }

    /**
     * 分组列表
     */
    public function image_option_grouping()
    {
        try {
            $projectId = $this->request->param('project_id');

            if (!$projectId) {
                return json(['code' => 400, 'msg' => '项目ID不能为空']);
            }

            $categories = ImageCategory::byProject($projectId)->select();
            $image_option_grouping = [];

            foreach ($categories as $categorie) {
                // 为每个分类创建一个分组
                $group = [
                    'label' => $categorie->type,
                    'options' => []
                ];

                $images = Image::getProjectImageUrlByCategory($projectId, $categorie['id']);
                foreach ($images as $image) {
                    // 为每个图片添加到当前分组的options中
                    $group['options'][] = [
                        'label' => $image->name,
                        'value' => $image->id,
                        'file' => $image->file
                    ];
                }

                // 将完整的分组添加到结果数组中
                $image_option_grouping[] = $group;
            }

            return success($image_option_grouping, '获取成功');
        } catch (\Exception $e) {
            return error('服务器错误：' . $e->getMessage());
        }
    }
    
    /**
     * 图片级联选择器
     */
    public function image_cascade_selector()
    {
        try {
            $projectId = $this->request->param('project_id');

            if (!$projectId) {
                return json(['code' => 400, 'msg' => '项目ID不能为空']);
            }

            $categories = ImageCategory::byProject($projectId)->select();
            $image_option_grouping = [];

            foreach ($categories as $categorie) {
                // 为每个分类创建一个分组
                $group = [
                    'label' => $categorie->type,
                    'children' => []
                ];

                $images = Image::getProjectImageUrlByCategory($projectId, $categorie['id']);
                foreach ($images as $image) {
                    $group['children'][] = [
                        'label' => $image->name,
                        'value' => $image->id,
                        'file' => $image->file
                    ];
                }

                // 将完整的分组添加到结果数组中
                $image_option_grouping[] = $group;
            }

            return success($image_option_grouping, '获取成功');
        } catch (\Exception $e) {
            return error('服务器错误：' . $e->getMessage());
        }
    }
}
