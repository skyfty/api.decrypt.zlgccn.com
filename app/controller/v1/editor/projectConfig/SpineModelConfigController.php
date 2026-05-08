<?php

declare(strict_types=1);

namespace app\controller\v1\editor\projectConfig;

use think\facade\Request;
use think\facade\Validate;
use think\facade\Filesystem;
use app\model\projectConfig\SpineModelConfig;

class SpineModelConfigController
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }
        $AttributeData = SpineModelConfig::where('project_id', $params['project_id'])->select();

        return success($AttributeData);
    }

    /**
     * 保存
     */
    public function save()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require',
            'model_name' => 'require',
            'animations' => 'require',
            'alpha' => 'require',
            'premultiplied_alpha' => 'require',
            'preserve_drawing_buffer' => 'require',
            'show_controls' => 'require',
            'status' => 'require',
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }
        $AttributeData = SpineModelConfig::where('project_id', $params['project_id'])->find();
        if (!$AttributeData) {
            $AttributeData = new SpineModelConfig();
        } else {
            // 删除旧文件
            $this->deleteFile($AttributeData->json_url);
            $this->deleteFile($AttributeData->atlas_url);
        }

        try {

            // 上传JSON文件到服务器
            $json_url = $this->uploadFile(Request::file('spine_json'), 'spine_json');
            $AttributeData->json_url = $json_url;
            // 上传图集文件到服务器
            $atlas_url = $this->uploadFile(Request::file('spine_atlas'), 'spine_atlas');
            $AttributeData->atlas_url = $atlas_url;

            $AttributeData->project_id = $params['project_id'];
            // 模型名称
            $AttributeData->model_name = $params['model_name'];
            $AttributeData->animations = $params['animations'];
            $AttributeData->alpha = $params['alpha'];
            $AttributeData->premultiplied_alpha = $params['premultiplied_alpha'];
            $AttributeData->preserve_drawing_buffer = $params['preserve_drawing_buffer'];
            $AttributeData->show_controls = $params['show_controls'];
            $AttributeData->status = $params['status'];
            $AttributeData->save();
            return success($AttributeData);
        } catch (\Exception $e) {
            return error($e->getMessage(), 400);
        }
    }

    // 上传文件到服务器到public/storage/resource/spine_json/或spine_atlas/目录
    public function uploadFile($file, $type)
    {
        $filePath = 'resource/' . $type . '/';
        $fileName = Filesystem::disk('public')->putFile($filePath, $file);
        return 'storage/' . ltrim($fileName, '/');
    }

    // 删除服务器上的文件
    public function deleteFile($filePath)
    {
        try {
            // 去掉开头的 'storage/' 前缀
            $pathToDelete = str_starts_with($filePath, 'storage/')
                ? substr($filePath, strlen('storage/'))
                : $filePath;

            Filesystem::disk('public')->delete($pathToDelete);
        } catch (\Exception $e) {
            \think\facade\Log::error('删除转场图片失败: ' . $e->getMessage());
        }
    }
}
