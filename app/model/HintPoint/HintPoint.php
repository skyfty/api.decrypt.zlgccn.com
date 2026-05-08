<?php

namespace app\model\HintPoint;
use think\Model;
use app\model\Image;

class HintPoint extends Model
{
    protected $table = 'hint_point';

    protected $pk = 'id';

    protected $type = [
        'id'        => 'integer',
        'room_id'   => 'integer',
        'help_type' => 'integer',
        'sort'      => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

    public function conditions()
    {
        return $this->hasMany(HintPointCondition::class, 'hint_point_id', 'id');
    }
    
    public function hintPointSpecialEffect()
    {
        return $this->hasOne(HintPointSpecialEffect::class, 'hint_point_id', 'id');
    }
    
    public function hintPointScaleUp()
    {
        return $this->hasOne(HintPointScaleUp::class, 'hint_point_id', 'id');
    }

    public function hintPointImage()
    {
        return $this->hasOne(HintPointImage::class, 'hint_point_id', 'id');
    }

    public function hintPointNumber()
    {
        return $this->hasOne(HintPointNumber::class, 'hint_point_id', 'id');
    }

    public function hintPointLetters()
    {
        return $this->hasOne(HintPointLetters::class, 'hint_point_id', 'id');
    }



    // 添加关联关系
    public static function param($hintPoint, $scale)
    {
        $typeToTableMap = [
            1 => HintPointSpecialEffect::class,
            2 => HintPointScaleUp::class,
            3 => HintPointImage::class,
            4 => HintPointNumber::class,
            5 => HintPointLetters::class,
        ];

        $modelClass = $typeToTableMap[$hintPoint->help_type] ?? null;

        if (!$modelClass) {
            return null;
        }
        $data = $modelClass::where('hint_point_id', $hintPoint->id)->find();
        if ($data) {
            switch ($hintPoint->help_type) {
                case 1: // 特效类型
                    // 可以添加特效相关的处理
                    break;
                case 2: // 放大类型
                    // 可以添加放大相关的处理
                    break;
                case 3: // 图片类型
                    if($scale){                    
                        $data['imageUrl'] = $data->image_id ? Image::getImageUrlById($data->image_id) . '?scale=' . $scale : '';
                    }else{
                        $data['imageUrl'] = $data->image_id ? Image::getImageUrlById($data->image_id) : '';
                    }
                    unset($data['image_id']);
                    break;
                case 4: // 数字类型
                    // 可以添加数字相关的处理
                    break;
                case 5: // 字母类型
                    // 可以添加字母相关的处理
                    break;
            }
        }
        return $data;
    }

    // 格式化hintPoint数据
    public static function formatHintPoints($hintPoints, $scale = 100)
    {
        foreach ($hintPoints as &$hintPoint) {
            $hintPoint['param'] = self::param($hintPoint, $scale);
        }
        return $hintPoints;
    }

}
