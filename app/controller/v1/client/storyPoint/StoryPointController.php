<?php

namespace app\controller\v1\client\storyPoint;

use app\BaseController;
use app\model\Image;
use app\model\StoryPoint\StoryPoint;
use app\model\Trans\TranslationKeyword;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroup;

class StoryPointController extends BaseController
{
    /**
     * 获取剧情点列表
     */
    public function index()
    {
        $roomId = $this->request->param('room_id/d', 0);
        $projectId = $this->request->param('project_id/d', 0);
        $scale = $this->request->param('scale/d', 100);

        if (!$roomId && !$projectId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $list = StoryPoint::with([
            'conditions',
            'actions'
        ])->where(['room_id' => $roomId, 'status' => true])->select();

        // 格式化数据
        $formattedList = [];
        foreach ($list as $storyPoint) {
            $formattedList[] = $this->formatStoryPoint($storyPoint, $scale);
        }

        return success($formattedList);
    }


    /**
     * 格式化剧情点数据
     */
    public function formatStoryPoint($storyPoint, $scale = 100)
    {
        // 基础字段格式化
        $formatted = [
            'id' => $storyPoint->id,
            'room_id' => $storyPoint->room_id,
            'name' => $storyPoint->name,
            'description' => $storyPoint->description,
            'conditions' => $this->formatCondition($storyPoint->conditions),
            'actions' => []
        ];

        // 格式化动作列表
        foreach ($storyPoint->actions as $action) {
            $formatted['actions'][] = $this->formatAction($action, $scale);
        }

        return $formatted;
    }

    /**
     * 格式化单个条件
     */
    private function formatCondition($conditions)
    {
        $formattedCondition = [];
        foreach ($conditions as $condition) {
            $formattedConditionData = [
                'id' => $condition->id,
                'story_point_id' => $condition->story_point_id,
                'type' => $condition->type,
                'param' => [],
            ];

            // 定义condition类型与格式化规则的映射表
            $conditionFormatMap = [
                'storyVariable' => [
                    'relation' => 'storyVariable',
                    'fields' => [
                        'variable_source' => 'variable_source',
                        'variable_id'     => 'variable_id',
                        'operators'       => 'operator',
                        'variable_value'  => 'variable_value'
                    ]
                ],
                'attribute' => [
                    'relation' => 'attribute',
                    'fields' => [
                        'key'      => 'key',
                        'operators' => 'operator',                // 同样映射
                        'value'    => 'value'
                    ]
                ]
            ];

            // 根据类型格式化参数
            if (isset($conditionFormatMap[$condition->type])) {
                $config = $conditionFormatMap[$condition->type];
                $relationData = isset($condition->{$config['relation']}) ? $condition->{$config['relation']} : null;

                // 空值保护：关联数据不存在时跳过
                if ($relationData) {
                    foreach ($config['fields'] as  $outputKey =>  $sourceField) {
                        $formattedConditionData['param'][$outputKey] =  $relationData->{$sourceField} ?? null;
                    }
                }
            }
            $formattedCondition[] = $formattedConditionData;
        }

        return $formattedCondition;
    }

    private function formatAction($action, $scale)
    {
        // 动作基础信息
        $actionData = [
            'id' => $action->id,
            'type' => $action->type
        ];

        // 定义类型与格式化规则的映射表
        $actionFormatMap = [
            'assignVariable' => [
                'relation' => 'assignVariable',
                'fields' => [
                    'variable_source' => 'variable_source',
                    'variable_id' => 'variable_id',
                    'assign_value' => 'assign_value',
                    'operation' => 'operation'
                ]
            ],
            'operateButtonPoint' => [
                'relation' => 'operateButtonPoint',
                'fields' => [
                    'buttonPoint_id' => 'buttonPoint_id',
                    'status' => 'status'
                ]
            ],
            'animationPlayMode' => [
                'relation' => 'animationPlayMode',
                'fields' => [
                    'buttonPoint_id' => 'buttonPoint_id',
                    'animation_play_count' => 'animation_play_count'
                ]
            ],
            'updateWeather' => [
                'relation' => 'updateWeather',
                'fields' => [
                    'weather_type' => 'weather_type',
                    'intensity' => 'intensity'
                ]
            ],
            'roleDialogue' => [
                'relation' => 'roleDialogue',
                'fields' => [
                    'character_name' => 'character_name',
                    'character_image' => 'character_image',
                    'expression_image' => 'expression_image',
                    'dialogue' => 'dialogue'
                ]
            ],
            'chapter' => [
                'relation' => 'chapter',
                'fields' => [
                    'background' => 'background',
                    'type' => 'chapter_type',
                    'duration' => 'duration',
                    'content' => 'content'
                ]
            ],
            'setAttribute' => [
                'relation' => 'setAttribute',
                'fields' => [
                    'attribute_id' => 'attribute_id',
                    'value' => 'value',
                    'operation' => 'operation'
                ]
            ]
        ];

        // 根据类型格式化参数
        if (isset($actionFormatMap[$action->type])) {
            $config = $actionFormatMap[$action->type];
            $relationData = isset($action->{$config['relation']}) ? $action->{$config['relation']} : null;

            // 空值保护：关联数据不存在时跳过
            if ($relationData) {
                foreach ($config['fields'] as  $outputKey =>  $sourceField) {
                    $actionData['param'][$outputKey] =  $relationData->{$sourceField} ?? null;
                }
            }
        }

        switch ($actionData['type']) {
            case 'roleDialogue':
                $actionData['param']['character_image'] = Image::getImageUrlById($actionData['param']['character_image'], $scale);
                $actionData['param']['expression_image'] = Image::getImageUrlById($actionData['param']['expression_image'], $scale);
                $actionData['param']['dialogue_Chinese'] = TranslationKeyword::queryKeyByName($actionData['param']['dialogue']);
                break;
            case 'chapter':
                $actionData['param']['content_Chinese'] = TranslationKeyword::queryKeyByName($actionData['param']['content']);
                $actionData['param']['background'] = Image::getImageUrlById($actionData['param']['background'], $scale);
                break;
            case 'roomOptionGroup':
                // 创建控制器实例
                $roomOptionGroupController = app()->make(\app\controller\v1\client\optionGroup\RoomOptionGroupController::class);
                $optionGroup = RoomOptionGroup::with([
                    'roomOptionGroupOption'
                ])->where('action_id', $action->id)->find();
                $actionData['param'] = $roomOptionGroupController->formatRoomOptionGroup($optionGroup);
                break;
        }

        $actionData['requiredConditionIds'] = $action->requiredConditions->column('id');
        return $actionData;
    }

}
