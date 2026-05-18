<?php

use think\facade\Route;
use app\middleware\Maintenance;

// --------------------------------------------------------------------------
//  端接口（只读/下载类）
// --------------------------------------------------------------------------


// 获取缩放后的项目数据
Route::get(
    'project/getScaledProjectData',
    'app\controller\v1\client\project_task\ProjectController@getScaledProjectData'
)->middleware(Maintenance::class);

Route::group('v1/client', function () {
    Route::group('/auth', function () {
        Route::get('DownloadProjectJson', 'v1.client.auth.DownloadProject/DownloadProjectJson');
        Route::get('GetProject', 'v1.client.auth.Project/GetProject');
        Route::get('getProjectCityRoomList', 'v1.client.auth.Project/getProjectCityRoomList');

        Route::get('QueryProjectData', 'v1.client.auth.QueryProjectData/GetProject');
        Route::get('TestDownloadProjectJson', 'v1.client.auth.QueryProjectData/TestDownloadProjectJson');

        Route::get('GetTestProject', 'v1.client.auth.TestProject/GetTestProject');
        Route::get('GetTestProjectCopy', 'v1.client.auth.TestProjectCopy/GetTestProjectCopy');
    });

    Route::group('/globalResources', function () {
        Route::get('GetPrivateAudios', 'v1.client.globalResources.privateAudio/GetPrivateAudios');
        Route::get('GetResources', 'v1.client.globalResources.Resources/GetResources');
        Route::get('GetItemResources', 'v1.client.globalResources.ItemResources/GetItemResources');

        // 请求单个物品数据
        Route::get('items', 'v1.client.globalResources.ItemResources/index');
        // 请求单个物品数据
        Route::get('items/:id', 'v1.client.globalResources.ItemResources/read');
        Route::get('GetSafeZoneResources', 'v1.client.globalResources.SafeZoneResources/GetSafeZoneResources');
        Route::get(
            'GetStoryVariables',
            'v1.client.globalResources.StoryVariablesResources/GetStoryVariables'
        );
    });

    Route::group('/privateConfig', function () {
        Route::get(
            'GetPrivateAudios',
            'v1.client.privateConfig.PrivateAudio/GetPrivateAudios'
        );
    });

    // 动画管理（animations表 和 animation_frames表）
    Route::group('/globalResources', function () {
        // 获取动画列表
        Route::get('GetAnimations', 'v1.client.globalResources.AnimationResources/GetAnimations');
        // 获取动画数据
        Route::get('GetAnimationActions',  'v1.client.globalResources.AnimationResources/GetAnimationActions');
        // 获取动画动作数据
        Route::get('GetAnimationFrames', 'v1.client.globalResources.AnimationResources/GetAnimationFrames');
        // 属性
        Route::get('attribute', 'v1.client.globalResources.ProjectAttribute/index');
    });

    // 获取StoryPoint
    Route::group('/story-point', function () {
        Route::get('/', 'v1.client.storyPoint.StoryPointController/index');
    });

    // 获取项目剧情数据
    Route::group('/projectStoryLine', function () {
        // 项目剧情列表
        Route::get('/', 'app\controller\v1\client\storyPoint\ProjectStoryLineController@index');
        // 获取翻译CSV文件
        Route::get('translateDialogue/export', 'app\controller\v1\client\storyPoint\TranslateStoryLineDialogue@index');
        // 预览翻译结果
        Route::get('translateDialogue/preview', 'app\controller\v1\client\storyPoint\TranslateStoryLineDialogue@preview');
    });

    // 项目发行管理
    Route::group('/projectRelease', function () {
        // 获取项目IOS发行材料
        Route::get('GetIOSPublishMaterials', 'v1.client.projectRelease.IOSPublishMaterial/GetIOSPublishMaterials');
    });

    // 界面管理面板 UI
    Route::group('/panel', function () {
        // 项目标题
        Route::get('GetTitle', 'v1.client.panel.Title/GetTitle');
        Route::get('GetTitleTest', 'v1.client.panel.TitleTest/GetTitle');
        // 更新
        Route::get('panelUpdate', 'v1.client.panel.Update/index');
        // 载入
        Route::get('panelLoading', 'v1.client.panel.Loading/index');
        // 转场
        Route::get('GetTransitionScene', 'v1.client.panel.TransitionScene/GetTransitionScene');
        // 游戏
        Route::get('GetGame', 'v1.client.panel.Game/GetGame');
        // 属性
        Route::get('attribute', 'v1.client.panel.Attribute/index');
        // 设置
        Route::get('GetSetting', 'v1.client.panel.Setting/GetSetting');
        // 对话框
        Route::get('dialogBox', 'v1.client.panel.DialogBoxController/index');
        // 章节
        Route::get('chapter', 'v1.client.panel.Chapter/index');
        // 物品栏
        Route::get('itemBar', 'v1.client.panel.ItemBar/index');
        // 提示
        Route::get('hint', 'v1.client.panel.Hint/GetHint');
    });
})->middleware(Maintenance::class);

// 图片动态缩放路由
// Route::get('image/resize/:imageId', function ($imageId) {
//     $scale = input('scale', 0.5); // 默认缩放为50%
//     $imageModel = new \app\model\Image();
//     $image = $imageModel->find($imageId);

//     if (!$image || !$image->file) {
//         return response('Image not found', 404);
//     }

//     // 这里简化处理，实际应该使用图片处理库如 Intervention Image
//     // 返回原图（实际项目中需要实现真正的缩放逻辑）
//     return redirect($image->file);
// });
