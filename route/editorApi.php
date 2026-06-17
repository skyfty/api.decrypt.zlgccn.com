<?php

use think\facade\Route;

// 【编辑类 - 项目保存类】
Route::group('v1/editor/auth', function () {
    Route::get('GetProject', 'v1.editor.auth.ProjectController/GetProject');
    Route::post('saveProject', 'v1.editor.auth.ConfigProject/saveProject');
    Route::post('saveCity', 'v1.editor.auth.ConfigCity/saveCity');
    Route::post('cloneCity', 'v1.editor.auth.ConfigCity/cloneCity');
    Route::post('saveRoom', 'v1.editor.auth.ConfigRoom/saveRoom');
    Route::post('cloneRoom', 'v1.editor.auth.ConfigRoom/cloneRoom');
    Route::get('/room_detail', 'v1.editor.auth.ConfigRoom/room_detail');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// 【编辑类 - 通用删除】
Route::group('v1/editor', function () {
    Route::post('delete', 'v1.editor.Common/delete');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// 【编辑类 - 排序功能】
Route::group('v1/editor/sort', function () {
    Route::post('updateRoomSort', 'v1.editor.sort.RoomSort/updateRoomSort');
    Route::post('updateButtonPointSort', 'v1.editor.sort.ButtonPointSort/updateButtonPointSort');
    Route::post('updateButtonPointGroupSort', 'v1.editor.sort.ButtonPointGroupSort/updateButtonPointGroupSort');
    Route::post('updateTopLevelEntrySort', 'v1.editor.sort.TopLevelEntrySort/updateTopLevelEntrySort');
    Route::post('updateHintPointSort', 'v1.editor.sort.HintPointSort/updateHintPointSort');
    Route::post('updateAnimationFrameSort', 'v1.editor.sort.AnimationFrameSort/updateAnimationFrameSort');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// 【编辑类 - 特殊点位】
Route::group('v1/editor/buttonPoint', function () {
    Route::post('newSaveButtonPoint', 'v1.editor.buttonPoint.ButtonPoint/newSaveButtonPoint');
    Route::post('updateButtonPointGroup', 'v1.editor.buttonPoint.ButtonPoint/updateButtonPointGroup');
    Route::post('updateButtonPointState', 'v1.editor.buttonPoint.ButtonPoint/updateButtonPointState');
    Route::post('cloneButtonPointGroup', 'v1.editor.buttonPoint.ButtonPoint/cloneButtonPointGroup');
    Route::post('cloneButtonPoint', 'v1.editor.buttonPoint.ButtonPoint/cloneButtonPoint');

    // 按钮控制器
    Route::get('/detail', 'v1.editor.buttonPoint.ButtonPointController/detail');

    // 按钮私有资源
    Route::get(
        'GetButtonPointResources',
        'v1.editor.buttonPoint.ButtonPointResources/GetButtonPointResources'
    ); // 查询ButtonPoint资源
    Route::post(
        'UpdateButtonPointResource',
        'v1.editor.buttonPoint.ButtonPointResources/UpdateButtonPointResource'
    ); // 更新ButtonPoint资源
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

Route::group('v1/editor/button_point_group', function () {
    Route::get('', 'v1.editor.buttonPointGroup.ButtonPointGroup/index');
    Route::post('', 'v1.editor.buttonPointGroup.ButtonPointGroup/save');
    Route::delete('', 'v1.editor.buttonPointGroup.ButtonPointGroup/delete');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// storyPoint
Route::group('v1/editor/storyPoint', function () {
    Route::get('GetStoryVariables', 'v1.editor.storyPoint.StoryVariables/GetStoryVariables');
    Route::post('SaveStoryVariable', 'v1.editor.storyPoint.StoryVariables/SaveStoryVariable');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// 房间剧情
Route::group('v1/editor/story-point', function () {
    Route::get('/', 'v1.editor.storyPoint.StoryPointController/index');
    Route::post('/', 'v1.editor.storyPoint.StoryPointController/save'); // 创建
    Route::put('/', 'v1.editor.storyPoint.StoryPointController/update');     // 更新
    Route::delete('/', 'v1.editor.storyPoint.StoryPointController/delete');  // 删除
});

// 获取项目剧情数据
Route::group('project', function () {
    Route::get('story_line', 'app\controller\v1\editor\storyPoint\ProjectStoryLineController@index');
    Route::post('story_line', 'app\controller\v1\editor\storyPoint\ProjectStoryLineController@create');
    Route::put('story_line', 'app\controller\v1\editor\storyPoint\ProjectStoryLineController@update');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

Route::group('v1/editor/hintPoint', function () {
    Route::post('newSaveHintPoint', 'v1.editor.hintPoint.HintPoint/newSaveHintPoint');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

// --------------------------------------------------------------------------
// 【编辑类 - 全局资源】
// 当前仍支持多种资源类型（如 image/audio/video/animation/model），但建议逐步拆分
// --------------------------------------------------------------------------
Route::group('v1/editor/globalResources', function () {
    // 通用资源获取（旧，支持多类型）
    Route::get('GetResources', 'v1.editor.globalResources.Resources/GetResources');
    Route::get('GetItemResources', 'v1.editor.globalResources.ItemResources/GetItemResources');
    Route::get('GetSafeZoneResources', 'v1.editor.globalResources.SafeZoneResources/GetSafeZoneResources');
    Route::get('GetStoryVariables', 'v1.editor.globalResources.StoryVariablesResources/GetStoryVariables');

    // 通用资源上传（旧，通过 type 区分，如 animation/image/audio...）
    Route::post('UploadResource', 'v1.editor.globalResources.Resources/UploadResource');
    Route::post('UploadItemResources', 'v1.editor.globalResources.ItemResources/UploadItemResources');
    Route::post('UploadSafeZoneResources', 'v1.editor.globalResources.SafeZoneResources/UploadSafeZoneResources');
    Route::post('SaveStoryVariable', 'v1.editor.globalResources.StoryVariablesResources/SaveStoryVariable');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);


// 获取图片分组数据
Route::group('project', function () {
    Route::get('image_categories', 'app\controller\v1\editor\globalResources\image_categories@index');
    Route::post('image_categories', 'app\controller\v1\editor\globalResources\image_categories@create');
    Route::get('image_option_grouping', 'app\controller\v1\editor\globalResources\image_categories@image_option_grouping');
    Route::get('image_cascade_selector', 'app\controller\v1\editor\globalResources\image_categories@image_cascade_selector');
});

// --------------------------------------------------------------------------
// 【新增】v1/editor/animation —— 动画管理（推荐新增独立路由组）
// --------------------------------------------------------------------------

// 动画管理（animations表 和 animation_frames表）
Route::group('v1/editor/globalResources', function () {
    // 动画基础信息
    Route::get('GetAnimations', 'v1.editor.globalResources.AnimationResources/GetAnimations');          // 获取项目动画列表
    Route::get('GetAnimationList', 'v1.editor.globalResources.AnimationResources/GetAnimationList');          // 获取项目动画完整列表
    Route::post('UploadAnimation', 'v1.editor.globalResources.AnimationResources/UploadAnimation');     // 新增/更新动画
    Route::delete('DeleteAnimation', 'v1.editor.globalResources.AnimationResources/DeleteAnimation');   // 删除动画

    // 动画动作管理
    Route::get('GetAnimationActions', 'v1.editor.globalResources.AnimationResources/GetAnimationActions');          // 获取项目动画动作列表
    Route::post('UploadAnimationAction', 'v1.editor.globalResources.AnimationResources/UploadAnimationAction');     // 上传/更新单动画动作
    Route::delete('DeleteAnimationAction', 'v1.editor.globalResources.AnimationResources/DeleteAnimationAction');   // 删除动画帧


    // 动画帧管理
    Route::post('UploadAnimationFrame', 'v1.editor.globalResources.AnimationResources/UploadAnimationFrame');   // 上传/更新单帧
    Route::get('GetAnimationFrames', 'v1.editor.globalResources.AnimationResources/GetAnimationFrames');        // 获取某动画的所有帧
    Route::delete('DeleteAnimationFrame', 'v1.editor.globalResources.AnimationResources/DeleteAnimationFrame'); // 删除动画帧
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);



// 项目发行管理（IOS_materials表）
Route::group('v1/editor/projectRelease', function () {
    // 项目IOS发行管理
    Route::get('GetIOSPublishMaterials', 'v1.editor.projectRelease.IOSPublishMaterial/GetIOSPublishMaterials');    // 获取iOS发布材料
    Route::post('UploadIOSPublishMaterial', 'v1.editor.projectRelease.IOSPublishMaterial/UploadIOSPublishMaterial'); // 上传iOS发布材料
    Route::delete('DeleteIOSPublishMaterial', 'v1.editor.projectRelease.IOSPublishMaterial/DeleteIOSPublishMaterial'); // 删除iOS发布材料

    Route::post('UploadIOSPublisIcon', 'v1.editor.projectRelease.IOSPublishMaterial/UploadIOSPublisIcon'); // 上传iOS发布图标

})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);

Route::group('v1/editor/projectRelease', function () {
    // 下载IOS发行材料压缩包
    Route::get('DownloadIOSPublishMaterialsZip', 'v1.editor.projectRelease.IOSPublishMaterialsDownloader/DownloadIOSPublishMaterialsZip');
});

Route::group('v1/projectRelease', function () {
    Route::post('iosSaveAuthInfo', 'app\controller\v1\editor\projectRelease\IOSPublishMaterial@save'); // 更新 / 创建 IOS_authInfo
});

// --------------------------------------------------------------------------
// 工具栏管理
// --------------------------------------------------------------------------

// 项目属性
Route::group('v1/editor/project_attribute', function () {
    Route::get('', 'v1.editor.globalResources.ProjectAttribute/index');
    Route::post('/save', 'v1.editor.globalResources.ProjectAttribute/save');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);


// --------------------------------------------------------------------------
// 选项组--Api
// --------------------------------------------------------------------------

Route::group('v1/editor/room_option_group', function () {
    Route::get('', 'v1.editor.optionGroup.RoomOptionGroupController/index');
    Route::post('', 'v1.editor.optionGroup.RoomOptionGroupController/save');
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);





// --------------------------------------------------------------------------
// 项目配置
// --------------------------------------------------------------------------

// 项目配置测试
Route::group('v1/editor', function () {
    // 项目配置
    Route::group('projectConfig', function () {
        // spine模型
        Route::group('SpineModel', function () {
            Route::get('', 'v1.editor.projectConfig.SpineModelConfigController/index');
            Route::post('', 'v1.editor.projectConfig.SpineModelConfigController/save');
        });
    });
});

// 项目配置
Route::group('v1/editor', function () {
    // hintPoint
    Route::group('hintPoint', function () {
        Route::get('', 'v1.editor.hintPoint.HintPointController/index');
        Route::post('', 'v1.editor.hintPoint.HintPointController/save');
    });

    // 界面
    Route::group('panel', function () {
        // 标题
        Route::get('GetTitle', 'v1.editor.panel.Title/GetTitle');
        Route::post('updateTitle', 'v1.editor.panel.Title/updateTitle');
        Route::post('UploadTitleItem', 'v1.editor.panel.Title/UploadTitleItem');
        Route::delete('DeleteTitleItem', 'v1.editor.panel.Title/DeleteTitleItem');

        // 更新
        Route::get('/UpdatePanel', 'v1.editor.panel.Update/index');
        Route::post('/createUpdatePanel', 'v1.editor.panel.Update/save');

        // 载入
        Route::get('/LoadingPanel', 'v1.editor.panel.Loading/index');
        Route::post('/createLoadingPanel', 'v1.editor.panel.Loading/save');

        // 转场
        Route::get('GetTransitionScene', 'v1.editor.panel.TransitionScene/GetTransitionScene');
        Route::post('UploadTransitionScene', 'v1.editor.panel.TransitionScene/UploadTransitionScene');
        Route::delete('DeleteTransitionScene', 'v1.editor.panel.TransitionScene/DeleteTransitionScene');

        // 游戏
        Route::group('game', function () {
            Route::get('', 'v1.editor.panel.Game/index');
            Route::post('/save', 'v1.editor.panel.Game/save');
            Route::delete('/delete', 'v1.editor.panel.Game/delete');
        });

        // 设置
        Route::get('GetSetting', 'v1.editor.panel.Setting/GetSetting');
        Route::post('UploadSetting', 'v1.editor.panel.Setting/UploadSetting');

        // 对话框
        Route::group('dialogBox', function () {
            Route::get('', 'v1.editor.panel.DialogBoxController/index');
            Route::put('', 'v1.editor.panel.DialogBoxController/save');
        });

        // 物品栏
        Route::group('itemBar', function () {
            Route::get('', 'v1.editor.panel.ItemBar/index');
            Route::put('', 'v1.editor.panel.ItemBar/save');
        });

        // 提示
        Route::group('hint', function () {
            Route::get('', 'v1.editor.panel.Hint/index');
            Route::post('/save', 'v1.editor.panel.Hint/save');
        });
    });

    // 面板属性
    Route::group('panel_attribute', function () {
        Route::get('', 'v1.editor.panel.Attribute/index');
        Route::post('', 'v1.editor.panel.Attribute/save');
        Route::delete('', 'v1.editor.panel.Attribute/delete');
    });

    // 面板章节
    Route::group('panel_chapter', function () {
        Route::get('', 'v1.editor.panel.Chapter/index');
        Route::post('/save', 'v1.editor.panel.Chapter/save');
        Route::delete('/delete', 'v1.editor.panel.Chapter/delete');
    });
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\AuthMiddleware::class
]);
