<?php

declare(strict_types=1);

namespace app\controller\v1\editor\projectRelease;

use think\facade\Request;
use think\facade\Db;
use ZipArchive;
use think\facade\Filesystem;
use app\model\projectRelease\ios\IOS_authInfo;
use think\facade\Log;


class IOSPublishMaterialsDownloader
{
    private $tempDir;
    /**
     * 下载iOS发布素材ZIP包
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

        // 定义 ZIP 存储目录：public/storage/resource/IOS/
        [ $zipFileName, $zipFilePath, $tempDir ] = $this->preparePaths($project_id);
        // $zipSaveDir = public_path('storage/resource/iOS/');
        // $zipFileName = 'iOS_Publish_Materials_Project_' . $project_id . '.zip';
        // $zipFilePath = $zipSaveDir . $zipFileName;

        // // 确保目录存在
        // if (!is_dir($zipSaveDir)) {
        //     if (!mkdir($zipSaveDir, 0755, true)) {
        //         return error('无法创建资源存储目录：' . $zipSaveDir, 500);
        //     }
        // }

        // // 检查目录是否可写
        // if (!is_writable($zipSaveDir)) {
        //     return error('资源存储目录不可写：' . $zipSaveDir, 500);
        // }

        // // 创建临时目录用于存储认证信息文件
        // $tempDir = public_path('runtime/temp_ios_auth_' . $project_id . '_' . time() . '/');
        // if (!is_dir($tempDir)) {
        //     if (!mkdir($tempDir, 0755, true)) {
        //         return error('无法创建临时目录：' . $tempDir, 500);
        //     }
        // }

        // 将认证信息保存为 JSON 文件
        $authFilePath = $this->createAuthInfoFile($tempDir, $iosAuthInfo);
        // $authJsonContent = json_encode($iosAuthInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // $authFileName = 'iOS_Auth_Info.json';
        // $authFilePath = $tempDir . $authFileName;

        // if (file_put_contents($authFilePath, $authJsonContent) === false) {
        //     // 清理临时目录
        //     $this->deleteDirectory($tempDir);
        //     return error('无法写入认证信息文件', 500);
        // }

        // 创建 ZIP 文件
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // 清理临时目录
            $this->deleteDirectory($tempDir);
            return error('无法创建 ZIP 文件', 500);
        }

        $addedFiles = 0;

        // 添加iPhone iPad, quick_app 素材文件到 ZIP
        $this->addMaterialFilesToZip($zip, $materials);
        // foreach ($materials as $material) {
        //     $specType = $material['spec_type'];
        //     $filePath = $material['image_path'];

        //     // 只处理 iPhone 和 iPad
        //     if (!in_array($specType, ['iPhone', 'iPad', 'quick_app'])) {
        //         continue;
        //     }

        //     // 检查文件是否存在且是正常文件
        //     if (!file_exists($filePath) || !is_file($filePath)) {
        //         continue;
        //     }

        //     // 在 ZIP 内的路径，比如：
        //     // 取原始扩展名，若拿不到就默认 png
        //     $ext     = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
        //     $fileNameInZip = 'file_' . $material['id']. '.' . $ext;
        //     $zipEntryPath = $specType . '/' . $fileNameInZip;

        //     // 添加文件到 ZIP
        //     if ($zip->addFile($filePath, $zipEntryPath)) {
        //         $addedFiles++;
        //     } else {
        //         // 可记录日志：添加失败
        //         Log::warning("无法添加文件到 ZIP: {$filePath} as {$zipEntryPath}");
        //     }
        // }

        // 处理 Icon 图标文件（与 JSON 平级）
        $this->addIconFileToZip($zip, $iosAuth);
        // $iconPath = $iosAuth['icon_url'] ?? '';
        // if ($iconPath && file_exists($iconPath) && is_file($iconPath)) {
        //     // 取原始扩展名，若拿不到就默认 png
        //     $ext     = pathinfo($iconPath, PATHINFO_EXTENSION) ?: 'png';
        //     $iconNameInZip = 'AppIcon.' . $ext;

        //     // 直接加进 ZIP（与 JSON 同一级）
        //     if ($zip->addFile($iconPath, $iconNameInZip)) {
        //         $addedFiles++;
        //     } else {
        //         Log::warning("无法添加图标到 ZIP: {$iconPath}");
        //     }
        // }


        // 添加认证信息文件到 ZIP 根目录
        $this->addAuthFileToZip($zip, $authFilePath);
        // if (file_exists($authFilePath) && is_file($authFilePath)) {
        //     $zip->addFile($authFilePath, 'iOS_Auth_Info.json');
        //     $addedFiles++;
        // }

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
     * 验证项目ID
     */
    private function validateProjectId($project_id)
    {
        if ($project_id <= 0) {
            throw error('项目ID不能为空', 400);
        }
    }

    /**
     * 获取项目和认证信息
     */
    private function fetchProjectData($project_id)
    {
        // 查询该项目的所有 iOS 素材
        $materials = Db::table('IOS_materials')
            ->where('project_id', $project_id)
            ->field('id, spec_type, image_path')
            ->select()
            ->toArray();

        if (empty($materials)) {
            throw error('该项目暂无 iOS 发布素材', 404);
        }

        // 查询认证信息
        $IOS_authInfo = Db::table('IOS_authInfo')
            ->where('project_id', $project_id)
            ->find();

        // 构建认证信息数组
        $iosAuthInfo = [
            'App 名称' => $IOS_authInfo['app_name'] ?? '尚未配置 App 名称',
            '关键字' => $IOS_authInfo['keyword'] ?? '',
            '描述' => $IOS_authInfo['desc'] ?? '',
            '技术支持网址' => $IOS_authInfo['technical_support_url'] ?? '',
            '营销网址' => $IOS_authInfo['market_url'] ?? '',
            '版本' => $IOS_authInfo['app_version'] ?? '',
            '版权' => $IOS_authInfo['app_copyright'] ?? '',
            '图标地址' => $IOS_authInfo['icon_url'] ?? '',
        ];


        return [$materials, $iosAuthInfo];
    }

    /**
     * 准备文件路径
     */
    private function preparePaths($project_id)
    {
        // 定义 ZIP 存储目录
        $zipSaveDir = public_path('storage/resource/iOS/');
        $zipFileName = 'iOS_Publish_Materials_Project_' . $project_id . '.zip';
        $zipFilePath = $zipSaveDir . $zipFileName;

        // 确保目录存在
        if (!is_dir($zipSaveDir)) {
            if (!mkdir($zipSaveDir, 0755, true)) {
                throw error('无法创建资源存储目录：' . $zipSaveDir, 500);
            }
        }

        // 检查目录是否可写
        if (!is_writable($zipSaveDir)) {
            throw error('资源存储目录不可写：' . $zipSaveDir, 500);
        }

        // 创建临时目录
        $tempDir = public_path('runtime/temp_ios_auth_' . $project_id . '_' . time() . '/');
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                throw error('无法创建临时目录：' . $tempDir, 500);
            }
        }

        return [$zipFileName, $zipFilePath, $tempDir];
    }

    /**
     * 创建认证信息文件
     */
    private function createAuthInfoFile($tempDir, $iosAuthInfo)
    {
        $authJsonContent = json_encode($iosAuthInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $authFileName = 'iOS_Auth_Info.json';
        $authFilePath = $tempDir . $authFileName;

        if (file_put_contents($authFilePath, $authJsonContent) === false) {
            // 清理临时目录
            $this->deleteDirectory($tempDir);
            throw error('无法写入认证信息文件', 500);
        }

        return $authFilePath;
    }

    /**
     * 创建ZIP包
     */
    private function createZipPackage($project_id, $materials, $iosAuthInfo, $zipFilePath, $authFilePath)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw error('无法创建 ZIP 文件', 500);
        }

        $addedFiles = 0;
        $errors = [];

        // 添加素材文件到 ZIP
        $addedFiles += $this->addMaterialFilesToZip($zip, $materials, $errors);

        // 添加图标文件到 ZIP
        $addedFiles += $this->addIconFileToZip($zip, $iosAuthInfo, $errors);

        // 添加认证信息文件到 ZIP
        $addedFiles += $this->addAuthFileToZip($zip, $authFilePath, $errors);

        $zip->close();

        // 检查是否有成功添加的文件
        if ($addedFiles === 0) {
            @unlink($zipFilePath);
            throw error('未找到任何有效的素材文件', 404);
        }

        // 返回成功信息和 ZIP 文件的访问 URL
        $downloadUrl = 'storage/resource/iOS/' . basename($zipFilePath);

        $responseData = [
            'message' => 'ZIP 文件已生成',
            'download_url' => $downloadUrl,
            'file_name' => basename($zipFilePath),
            'total_files' => $addedFiles,
            'includes_auth_info' => true
        ];

        // 如果有错误信息，也一并返回（但不影响成功状态）
        if (!empty($errors)) {
            $responseData['warnings'] = $errors;
            $responseData['message'] .= ' (部分文件存在问题)';
        }

        return success($responseData, '打包成功');
    }

    /**
     * 添加素材文件到ZIP
     */
    private function addMaterialFilesToZip(ZipArchive $zip, $materials)
    {
        $addedCount = 0;
        $allowedSpecTypes = ['iPhone', 'iPad', 'quick_app'];

        foreach ($materials as $material) {
            $specType = $material['spec_type'];
            $filePath = $material['image_path'];

            // 只处理允许的规格类型
            if (!in_array($specType, $allowedSpecTypes)) {
                continue;
            }

            // 检查文件是否存在且是正常文件
            if (!$this->isValidFile($filePath)) {
                error("素材文件不存在或无效: {$filePath}");
                continue;
            }

            // 在 ZIP 内的路径
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $fileNameInZip = 'file_' . $material['id'] . '.' . $ext;
            $zipEntryPath = $specType . '/' . $fileNameInZip;

            // 添加文件到 ZIP
            if ($zip->addFile($filePath, $zipEntryPath)) {
                $addedCount++;
            } else {
                $errors[] = "无法添加素材文件到ZIP: {$filePath}";
                Log::warning("无法添加文件到 ZIP: {$filePath} as {$zipEntryPath}");
            }
        }
        return $addedCount;
    }

    /**
     * 添加图标文件到ZIP
     */
    private function addIconFileToZip(ZipArchive $zip, $iosAuthInfo)
    {
        $addedCount = 0;
        $iconPath = $iosAuthInfo['icon_url'] ?? '';

        if ($this->isValidFile($iconPath)) {
            // 取原始扩展名，若拿不到就默认 png
            $ext = pathinfo($iconPath, PATHINFO_EXTENSION) ?: 'png';
            $iconNameInZip = 'AppIcon.' . $ext;

            // 直接加进 ZIP（与 JSON 同一级）
            if ($zip->addFile($iconPath, $iconNameInZip)) {
                $addedCount++;
            } else {
                Log::warning("无法添加图标到 ZIP: {$iconPath}");
            }
        }
        return $addedCount;
    }

    /**
     * 添加认证信息文件到ZIP
     */
    private function addAuthFileToZip(ZipArchive $zip, $authFilePath)
    {
        $addedCount = 0;

        if (file_exists($authFilePath) && is_file($authFilePath)) {
            $zip->addFile($authFilePath, 'iOS_Auth_Info.json');
            $addedCount++;
        }

        return $addedCount;
    }

    /**
     * 验证文件是否有效
     */
    private function isValidFile($filePath)
    {
        return !empty($filePath) && file_exists($filePath) && is_file($filePath);
    }

    /**
     * 递归删除目录
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }
}
