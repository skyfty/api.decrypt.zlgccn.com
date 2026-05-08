<?php

namespace app\controller\v1\client\optionGroup;

use app\BaseController;
use think\facade\Validate;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroup;
use app\model\Trans\TranslationKeyword;

class RoomOptionGroupController extends BaseController
{
    /**
     * 获取选项组
     */
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'action_id' => 'require'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $roomOptionGroups = RoomOptionGroup::with([
            'roomOptionGroupOption'
        ])->where('action_id', $params['action_id'])->select();

        // 格式化数据
        $formattedData = [];
        foreach ($roomOptionGroups as $optionGroup) {
            $formattedData[] = $this->formatRoomOptionGroup($optionGroup);
        }

        return success($formattedData);
    }

    #region 格式化选项组数据
    /**
     * 格式化选项组数据
     */
    public function formatRoomOptionGroup($optionGroup)
    {
        $formatted = [
            'id' => $optionGroup->id,
            'action_id' => $optionGroup->action_id,
            'name' => $optionGroup->name,
            'options' => []
        ];

        // 格式化选项列表
        foreach ($optionGroup->roomOptionGroupOption as $option) {
            $formatted['options'][] = $this->formatOption($option);
        }

        return $formatted;
    }

    /**
     * 格式化单个选项
     */
    private function formatOption($option)
    {
        $optionData = [
            'id' => $option->id,
            'option_group_id' => $option->option_group_id,
            'name' => $option->name,
            'actions' => []
        ];

        foreach ($option->roomOptionGroupOptionAction as $action) {
            $optionData['actions'][] = $this->formatAction($action);
        }


        return $optionData;
    }

    // 格式化单个选项的处理
    private function formatAction($action)
    {
        // 动作基础信息
        $actionData = [
            'id' => $action->id,
            'option_id' => $action->option_id,
            'type' => $action->type
        ];

        // 定义类型与格式化规则的映射表
        $actionFormatMap = [
            'message' => [
                'relation' => 'roomOptionGroupOptionActionMsg',
                'fields' => [
                    'option_action_id' => 'option_action_id',
                    'message' => 'message'
                ]
            ],
            'modifyStoryValue' => [
                'relation' => 'roomOptionGroupOptionActionModifyStoryValue',
                'fields' => [
                    'option_action_id' => 'option_action_id',
                    'target' => 'target',
                    'key' => 'key',
                    'operation' => 'operation',
                    'value' => 'value',
                ]
            ]
        ];

        // 根据类型格式化参数
        if (isset($actionFormatMap[$action->type])) {
            $config = $actionFormatMap[$action->type];
            $relationData = isset($action->{$config['relation']}) ? $action->{$config['relation']} : null;

            // 空值保护：关联数据不存在时跳过
            if ($relationData) {
                foreach ($config['fields'] as $field) {
                    $actionData['param'][$field] = isset($relationData->{$field}) ? $relationData->{$field} : null;
                }
                switch($action->type){
                    case 'message':
                        $actionData['param']['message_Chinese'] = TranslationKeyword::queryKeyByName($actionData['param']['message']);
                        break;
                }
            }
        }

        return $actionData;
    }

    #endregion


}
