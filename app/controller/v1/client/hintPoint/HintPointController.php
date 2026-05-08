<?php

namespace app\controller\v1\client\hintPoint;

use app\model\Image;
use app\model\HintPoint\HintPoint;

class HintPointController
{
    /**
     * 获取提示点列表
     */
    private $scale = 100;
    public function index($room_id, $scale = 100)
    {
        $this->scale = $scale;
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

    private function formatStoryPoint($data){
        $formatted = [
            'id' => $data->id,
            'room_id' => $data->room_id,
            'name' => $data->name,
            // 'sort' => $data->sort,
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
                $formatted['param']['image_url'] = Image::getImageUrlById($formatted['param']['image_id'] , $this->scale);
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

}
