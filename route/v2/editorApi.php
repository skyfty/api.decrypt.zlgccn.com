<?php

use think\facade\Route;

Route::group('v2/editor', function () {
        Route::group('auth', function () {
                Route::group('project', function () {
                        Route::get('', 'v2.editor.auth.ProjectController/index');
                });
        });

        // hintPoint
        Route::group('hintPoint', function () {
                Route::get('', 'v2.editor.hintPoint.HintPointController/index');
                Route::post('', 'v2.editor.hintPoint.HintPointController/save');
                Route::group('condition', function () {
                        Route::delete('', 'v2.editor.hintPoint.HintPointController/deleteCondition');
                });
        });

        // storyPoint
        Route::group('storyPoint', function () {
                Route::group('room', function () {
                        Route::get('', 'v2.editor.storyPoint.RoomStoryPointController/index');
                        Route::post('', 'v2.editor.storyPoint.RoomStoryPointController/save');
                        Route::delete('', 'v2.editor.storyPoint.RoomStoryPointController/delete');
                });
        });
})->middleware([
        \app\middleware\Cors::class,
        \app\middleware\AuthMiddleware::class
]);
