<?php

declare(strict_types=1);

namespace app\controller\v1\editor\projectRelease;

use think\facade\Request;
use think\facade\Db;
use ZipArchive;
use think\facade\Filesystem;
use app\model\projectRelease\ios\IOS_authInfo;
use think\facade\Log;

class IOSPublishMaterial
{
    /**
     * 获取指定项目的IOS发行材料
     * GET /v1/editor/projectRelease/GetIOSPublishMaterials
     *
     * 参数：
     *   project_id  int  必选
     */
    public function GetIOSPublishMaterials()
    {
        $project_id = (int) Request::param('project_id', 0);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 查询数据
        $list = Db::table('IOS_materials')
            ->where('project_id', $project_id)
            ->select()
            ->toArray();

        $IOS_authInfo = Db::table('IOS_authInfo')
            ->where('project_id', $project_id)
            ->find();
        $iosAuth = $IOS_authInfo ?? [];
        // 先初始化分组，确保 iPhone 和 iPad 都存在，哪怕没有数据
        $groupedData = [
            'IOS_authInfo' => [
                'id' => $iosAuth['id'] ?? null,
                'project_id' => $project_id,
                'app_name' => $iosAuth['app_name'] ?? '',
                'keyword' => $iosAuth['keyword'] ?? '',
                'desc' => $iosAuth['desc'] ?? '',
                'technical_support_url' => $iosAuth['technical_support_url'] ?? '',
                'market_url' => $iosAuth['market_url'] ?? '',
                'app_version' => $iosAuth['app_version'] ?? '',
                'app_copyright' => $iosAuth['app_copyright'] ?? '',
                'icon_url' => $iosAuth['icon_url'] ?? '',
            ],
            'iPhone'        =>  [],
            'iPad'          =>  [],
            'quick_app'     =>  []
        ];

        // 遍历查询结果，填充到对应分组
        foreach ($list as $item) {
            $specType = $item['spec_type'];
            if (isset($groupedData[$specType])) {
                $groupedData[$specType][] = $item;
            }
        }

        // 返回分组后的数据（现在 iPhone 和 iPad 一定存在，可能为空数组）
        return success($groupedData, '获取成功');
    }

    /**
     * 上传/更新指定项目的IOS发行材料
     * POST /v1/editor/projectRelease/UploadIOSPublishMaterial
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   project_id     int     必填    项目ID
     */
    public function UploadIOSPublishMaterial()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id             = (int) Request::post('id', 0);
        $project_id     = (int) Request::post('project_id', 0);
        $spec_type     = Request::post('spec_type', 'iPad');

        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }

        // 定义合法的设备类型白名单（枚举）
        $allowedSpecTypes = ['iPad', 'iPhone', 'quick_app'];

        // 校验 spec_type 是否为空 或 不在白名单之中
        if (empty($spec_type)) {
            return error('发行设备类型不能为空', 400);
        }

        if (!in_array($spec_type, $allowedSpecTypes)) {
            return error('发行设备类型不支持', 400);
        }

        // 计算当前最大排序
        $maxSort = Db::table('IOS_materials')
            ->where([
                'project_id'    =>  $project_id,
                'spec_type'     =>  $spec_type
            ])
            ->count();
        if ($maxSort === 5) {
            return error('IOS_' . $spec_type . '材料已上限.', 400);
        }

        $file = Request::file('image_path');
        if (!$file || !$file->isValid()) {
            return error('未上传宣传图片或上传失败', 400);
        }

        $row = [
            'spec_type'     =>  $spec_type,
            'update_time'   => date('Y-m-d H:i:s'),
        ];

        $saveDir = "resource/projectRelease/IOS";
        $saveName = Filesystem::disk('public')->putFile($saveDir, $file);
        $image_path = 'storage/' . ltrim($saveName, '/');

        $row['project_id'] = $project_id;
        $row['image_path'] = $image_path;
        $row['create_time'] = date('Y-m-d H:i:s');

        // ✅ 新增材料到数据库
        $row['id'] = Db::table('IOS_materials')->insertGetId($row);

        return success($row, $id > 0 ? '更新成功' : 'IOS ' . $spec_type . '发行材料审核通过.');
    }

    /**
     * 删除指定项目的IOS发行材料
     * Delete /v1/editor/projectRelease/UploadIOSPublishMaterial
     * 
     * 表单参数：
     *   id int 必须，动画ID
     */
    public function DeleteIOSPublishMaterial()
    {
        $id = (int) Request::get('id'); // ✅ 从 GET 参数获取，因为是 URL query

        if (empty($id) || $id <= 0) {
            return error('审核材料ID必须为有效正整数', 400);
        }

        $table = 'IOS_materials';
        $materials = Db::table($table)->find($id);
        if (!$materials) {
            return error('审核材料不存在', 404);
        }
        // 🔒 获取图片路径字段（假设字段叫 frameImage）
        $image_path = $materials['image_path'] ?? null;

        $result = Db::table($table)->where('id', $id)->delete();

        if ($result) {

            // 2. 如果有图片路径，尝试删除磁盘上的文件
            if (!empty($image_path)) {
                try {
                    // 去掉开头的 'storage/' 前缀
                    $pathToDelete = str_starts_with($image_path, 'storage/')
                        ? substr($image_path, strlen('storage/'))
                        : $image_path;

                    \think\facade\Filesystem::disk('public')->delete($pathToDelete);
                } catch (\Exception $e) {
                    \think\facade\Log::error('删除动画帧图片失败: ' . $e->getMessage());
                }
            }
            return success(null, '审核材料删除成功');
        } else {
            return error('审核材料删除失败', 500);
        }
    }

    /**
     * 更新 / 保存
     * 
     */
    public function save()
    {
        $data = request()->post();

        // 验证必要参数
        if (empty($data['project_id'])) {
            return json(['code' => 400, 'message' => '关联项目ID不能为空']);
        }

        try {
            if (!empty($data['id'])) {
                // 更新操作
                $keyword = IOS_authInfo::find($data['id']);
                if (!$keyword) {
                    return json(['code' => 404, 'message' => '记录不存在']);
                }
                $data['update_time'] = date('Y-m-d H:i:s');
                $result = $keyword->save($data);
            } else {
                // 创建操作
                unset($data['id']);
                $data['create_time'] = date('Y-m-d H:i:s');
                $data['update_time'] = date('Y-m-d H:i:s');
                $keyword = new IOS_authInfo;
                $result = $keyword->save($data);
            }

            if ($result) {
                return success($keyword, !empty($data['id']) ? '更新成功' : '创建成功');
            } else {
                return error('操作失败, 500');
            }
        } catch (\Exception $e) {
            return error('操作失败：' . $e->getMessage(),  500);
        }
    }

    /**
     * 上传iOS发布图标
     * POST /v1/editor/projectRelease/UploadIOSPublisIcon
     *
     * 表单字段：
     *   id             int     IOS认证信息-id
     *   icon_url       file    Icon图标
     */
    public function UploadIOSPublisIcon()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id = (int) Request::post('id', 0);

        if (empty($id)) {
            return error('IOS认证信息-id-不能为空.', 400);
        }

        $file = Request::file('icon_url');
        if (!$file || !$file->isValid()) {
            return error('未上传宣传图片或上传失败', 400);
        }

        try {

            $saveDir = "resource/projectRelease/ios-icon";
            $saveName = Filesystem::disk('public')->putFile($saveDir, $file);
            $icon_url = 'storage/' . ltrim($saveName, '/');

            $row = [
                'icon_url'   => $icon_url,
                'update_time'   => date('Y-m-d H:i:s'),
            ];

            // 更新操作
            $authInfo = IOS_authInfo::find($id);
            if (!$authInfo) {
                return json(['code' => 404, 'message' => '记录不存在']);
            }

            // 2. 如果有图片路径，尝试删除磁盘上的文件
            if (!empty($authInfo->icon_url)) {
                try {
                    // 去掉开头的 'storage/' 前缀
                    $pathToDelete = str_starts_with($authInfo->icon_url, 'storage/')
                        ? substr($authInfo->icon_url, strlen('storage/'))
                        : $authInfo->icon_url;

                    \think\facade\Filesystem::disk('public')->delete($pathToDelete);
                } catch (\Exception $e) {
                    \think\facade\Log::error('删除动画帧图片失败: ' . $e->getMessage());
                }
            }

            $result = $authInfo->save($row);
            return success($result, '上传iOS发布图标成功.');
        } catch (\Exception $e) {
            return error('上传iOS发布图标失败' . $e->getMessage(),  500);
        }
    }




    /**
     * 下载指定项目的 iOS 发布素材压缩包
     * GET /v1/client/projectRelease/DownloadIOSPublishMaterialsZip
     *
     * 参数：
     *   project_id  int  必选
     */
    public function DownloadIOSPublishMaterialsZip()
    {
        // 1. 获取并验证 project_id
        $project_id = (int) Request::param('project_id', 0);
        if ($project_id <= 0) {
            return error('项目ID不能为空', 400);
        }

        // 2. 查询该项目的所有 iOS 的 iPhone iPad, quick_app 素材
        $materials = Db::table('IOS_materials')
            ->where('project_id', $project_id)
            ->field('id, spec_type, image_path')
            ->select()
            ->toArray();

        if (empty($materials)) {
            return error('该项目暂无 iOS 发布素材', 404);
        }

        $IOS_authInfo = Db::table('IOS_authInfo')
            ->where('project_id', $project_id)
            ->find();

        if (empty($IOS_authInfo)) {
            return error('该项目暂未配置 iOS 认证信息', 404);
        }

        $iosAuth = $IOS_authInfo ?? [];
        $iosAuthInfo = [
            'App 名称' => $iosAuth['app_name'] ?? '尚未配置 App 名称',
            '关键字' => $iosAuth['keyword'] ?? '',
            '描述' => $iosAuth['desc'] ?? '',
            '技术支持网址' => $iosAuth['technical_support_url'] ?? '',
            '营销网址' => $iosAuth['market_url'] ?? '',
            '版本' => $iosAuth['app_version'] ?? '',
            '版权' => $iosAuth['app_copyright'] ?? '',
        ];

        // 3. 定义 ZIP 存储目录：public/storage/resource/IOS/
        $zipSaveDir = public_path('storage/resource/iOS/');
        $zipFileName = 'iOS_Publish_Materials_Project_' . $project_id . '.zip';
        $zipFilePath = $zipSaveDir . $zipFileName;

        // 确保目录存在
        if (!is_dir($zipSaveDir)) {
            if (!mkdir($zipSaveDir, 0755, true)) {
                return error('无法创建资源存储目录：' . $zipSaveDir, 500);
            }
        }

        // 可选：检查目录是否可写
        if (!is_writable($zipSaveDir)) {
            return error('资源存储目录不可写：' . $zipSaveDir, 500);
        }

        // 创建临时目录用于存储认证信息文件
        $tempDir = public_path('runtime/temp_ios_auth_' . $project_id . '_' . time() . '/');
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                return error('无法创建临时目录：' . $tempDir, 500);
            }
        }

        // 将认证信息保存为 JSON 文件
        $authJsonContent = json_encode($iosAuthInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $authFileName = 'iOS_Auth_Info.json';
        $authFilePath = $tempDir . $authFileName;

        if (file_put_contents($authFilePath, $authJsonContent) === false) {
            // 清理临时目录
            $this->deleteDirectory($tempDir);
            return error('无法写入认证信息文件', 500);
        }

        // 创建 ZIP 文件
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // 清理临时目录
            $this->deleteDirectory($tempDir);
            return error('无法创建 ZIP 文件', 500);
        }

        $addedFiles = 0;

        // 添加iPhone iPad, quick_app 素材文件到 ZIP
        foreach ($materials as $material) {
            $specType = $material['spec_type'];
            $filePath = $material['image_path'];

            // 只处理 iPhone 和 iPad
            if (!in_array($specType, ['iPhone', 'iPad', 'quick_app'])) {
                continue;
            }

            // 检查文件是否存在且是正常文件
            if (!file_exists($filePath) || !is_file($filePath)) {
                continue;
            }

            // 在 ZIP 内的路径，比如：
            // 取原始扩展名，若拿不到就默认 png
            $ext     = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $fileNameInZip = 'file_' . $material['id']. '.' . $ext;
            $zipEntryPath = $specType . '/' . $fileNameInZip;

            // 添加文件到 ZIP
            if ($zip->addFile($filePath, $zipEntryPath)) {
                $addedFiles++;
            } else {
                // 可记录日志：添加失败
                Log::warning("无法添加文件到 ZIP: {$filePath} as {$zipEntryPath}");
            }
        }

        // 处理图标文件（与 JSON 平级）
        $iconPath = $iosAuth['icon_url'] ?? '';
        if ($iconPath && file_exists($iconPath) && is_file($iconPath)) {
            // 取原始扩展名，若拿不到就默认 png
            $ext     = pathinfo($iconPath, PATHINFO_EXTENSION) ?: 'png';
            $iconNameInZip = 'AppIcon.' . $ext;

            // 直接加进 ZIP（与 JSON 同一级）
            if ($zip->addFile($iconPath, $iconNameInZip)) {
                $addedFiles++;
            } else {
                Log::warning("无法添加图标到 ZIP: {$iconPath}");
            }
        }


        // 添加认证信息文件到 ZIP 根目录
        if (file_exists($authFilePath) && is_file($authFilePath)) {
            $zip->addFile($authFilePath, $authFileName);
            $addedFiles++; // 认证信息文件也算一个有效文件
        }

        $zip->close();

        // 清理临时目录
        $this->deleteDirectory($tempDir);

        // 检查是否有成功添加的文件
        if ($addedFiles === 0) {
            // 可选：删除空的 zip 文件
            @unlink($zipFilePath);
            return error('未找到任何有效的素材文件', 404);
        }

        // 返回成功信息和 ZIP 文件的访问 URL
        $downloadUrl = 'storage/resource/iOS/' . $zipFileName;

        return success([
            'message' => 'ZIP 文件已生成',
            'download_url' => $downloadUrl,
            'file_name' => $zipFileName,
            'total_files' => $addedFiles,
            'includes_auth_info' => true
        ], '打包成功');
    }

    /**
     * 递归删除目录及其内容
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . $file;
            is_dir($path) ? $this->deleteDirectory($path . '/') : unlink($path);
        }
        rmdir($dir);
    }
}
