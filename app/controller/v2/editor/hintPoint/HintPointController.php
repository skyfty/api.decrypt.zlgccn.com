<?php

namespace app\controller\v2\editor\hintPoint;

use think\exception\ValidateException;
use app\model\HintPoint\HintPoint;

use app\model\HintPoint\HintPointCondition;
use app\model\HintPoint\HintPointImage;
use app\model\HintPoint\HintPointLetters;
use app\model\HintPoint\HintPointNumber;
use app\model\HintPoint\HintPointScaleUp;
use app\model\HintPoint\HintPointSpecialEffect;

class HintPointController
{
    /**
     * 获取提示点列表
     */
    public function index($room_id)
    {
        // $params = request()->param();

        // // 验证基础数据
        // $validate = Validate([
        //     'room_id'    => 'require|number',
        // ]);

        // if (!$validate->check($params)) {
        //     return error($validate->getError(), 500);
        // }

        $queryData = HintPoint::with([
            'conditions',
            'hintPointImage',
            'hintPointLetters',
            'hintPointNumber',
            'hintPointScaleUp',
            'hintPointSpecialEffect',
        ])->where('room_id', $room_id)->select();

        // 格式化数据
        $formattedList = [];
        foreach ($queryData as $hintPoint) {
            $formattedList[] = $this->formatStoryPoint($hintPoint);
        }
        return $formattedList;
    }

    // 保存提示点
    public function save()
    {
        $params = request()->post();

        // 验证参数
        $validate = Validate([
            'room_id' => 'require|number',
            'name' => 'require',
            'type' => 'require|number|in:1,2,3,4,5',
            'param' => 'require',
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError());
        }

        $type = $params['type'];
        $condition = $params['condition'];
        $param = $params['param'];

        try {
            // 存在ID时，更新数据
            $findHintPoint = !empty($params['id']) ? HintPoint::find($params['id']) : new HintPoint();

            // 判断新旧类型是否相同
            if (!empty($params['id'])) {
                if ($findHintPoint->type != $type) {
                    // 根据类型删除的类型参数
                    $this->deleteParam($findHintPoint);
                }
            }

            // 保存数据
            $findHintPoint->room_id = $params['room_id'];
            $findHintPoint->name = $params['name'];
            $findHintPoint->type = $type;
            $findHintPoint->save();

            // 保存条件
            $this->saveCondition($findHintPoint, $condition);

            // 保存参数
            $this->saveParam($findHintPoint, $param);

            return success($params, '更新成功');
        } catch (ValidateException $e) {
            throw new ValidateException($e->getMessage());
        }
    }

    // 删除参数
    private function deleteParam($findHintPoint)
    {
        switch ($findHintPoint->type) {
            case 1:
                HintPointSpecialEffect::where('hint_point_id', $findHintPoint->id)->delete();
                break;
            case 2:
                HintPointScaleUp::where('hint_point_id', $findHintPoint->id)->delete();
                break;
            case 3:
                HintPointImage::where('hint_point_id', $findHintPoint->id)->delete();
                break;
            case 4:
                HintPointNumber::where('hint_point_id', $findHintPoint->id)->delete();
                break;
            case 5:
                HintPointLetters::where('hint_point_id', $findHintPoint->id)->delete();
                break;
        }
    }

    // 保存条件
    private function saveCondition($findHintPoint, $condition)
    {
        if (empty($condition)) {
            HintPointCondition::where('hint_point_id', $findHintPoint->id)->delete();
            return;
        }
        foreach ($condition as $conditionItem) {
            $findCondition = empty($conditionItem['id']) ? new HintPointCondition() : HintPointCondition::where('id', $conditionItem['id'])->find() ?? new HintPointCondition();
            $findCondition->hint_point_id = $findHintPoint->id;
            $findCondition->condition = $conditionItem['condition'];
            $findCondition->min = $conditionItem['min'];
            $findCondition->max = $conditionItem['max'];
            $findCondition->save();
        }
    }

    // 保存参数
    private function saveParam($findHintPoint, $param)
    {
        $typeConfig = [
            '1' => [
                'class'  => HintPointSpecialEffect::class,
                'fields' => ['button_point_id']
            ],
            '2' => [
                'class'  => HintPointScaleUp::class,
                'fields' => ['button_point_id']
            ],
            '3' => [
                'class'  => HintPointImage::class,
                'fields' => ['image_id', 'width', 'height']
            ],
            '4' => [
                'class'  => HintPointNumber::class,
                'fields' => ['button_point_id']
            ],
            '5' => [
                'class'  => HintPointLetters::class,
                'fields' => ['hint_text']
            ],
        ];

        $type = $findHintPoint->type;

        if (!isset($typeConfig[$type])) {
            throw new \InvalidArgumentException("Unsupported condition type: {$type}");
        }

        $config = $typeConfig[$type];
        $class = $config['class'];
        $fields = $config['fields'];

        try {
            $model = $class::where('hint_point_id', $findHintPoint->id)->find() ?? new $class();

            // 设置关联 ID
            $model->hint_point_id = $findHintPoint->id;

            // 赋值参数字段
            $paramData = $param ?? [];
            foreach ($fields as $field) {
                $model->{$field} = $paramData[$field];
            }

            // 保存
            $model->save();
        } catch (\Exception $e) {
            // 保留原始异常信息
            throw new \Exception("Failed to save condition param for type {$type}: " . $e->getMessage(), 0, $e);
        }
    }

    private function formatStoryPoint($data)
    {
        $formatted = [
            'id' => $data->id,
            'room_id' => $data->room_id,
            'name' => $data->name,
            'sort' => $data->sort,
            'type' => $data->type,
            'condition' => $data->conditions
        ];

        // 格式化参数
        switch ($data->type) {
            case 1:
                $formatted['param'] = $data->hintPointSpecialEffect;
                break;
            case 2:
                $formatted['param'] = $data->hintPointScaleUp;
                break;
            case 3:
                $formatted['param'] = $data->hintPointImage;
                break;
            case 4:
                $formatted['param'] = $data->hintPointNumber;
                break;
            case 5:
                $formatted['param'] = $data->hintPointLetters;
                break;
        }

        return $formatted;
    }


    // 删除条件
    public function deleteCondition()
    {
        $params = request()->param();
        // 验证基础数据
        $validate = Validate([
            'id'    => 'require|number',
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError(), 500);
        }

        $findHintPoint = HintPointCondition::where('id', $params['id'])->find();
        if (!$findHintPoint) {
            return error('提示点条件不存在或已删除.', 500);
        }
        try {
            HintPointCondition::destroy($params['id']);
            return success('删除成功');
        } catch (\Exception $e) {
            return error('删除失败', 500);
        }
    }
}
