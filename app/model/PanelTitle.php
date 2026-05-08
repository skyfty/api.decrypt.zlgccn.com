<?php

namespace app\model;

class PanelTitle extends BaseModel
{
    protected $table = 'panel_title';

    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'project_id' => 'integer',
        'background_id' => 'integer',
    ];

    protected $hidden = ['background_id', 'background_audio', 'create_time', 'update_time'];

    // 项目关联
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // 项目项关联
    public function buttonPointList()
    {
        return $this->hasMany(PanelTitleItem::class, 'panel_title_id')->order('order', 'asc');
    }

    public static function formatPanelTitle($panelTitle, $scale = 100)
    {
        foreach ($panelTitle as &$item) {
            if($scale){
                $item->width = self::getScaledDimension($item->width, $scale);
                $item->height = self::getScaledDimension($item->height, $scale);
            }
            $backgroundId = $item->background_id;
            $imageUrl = Image::getImageUrlById($backgroundId);
            $item->imageUrl = $scale ? $imageUrl . '?scale=' . $scale : $imageUrl;
            $item->backgroundAudioUrl = Audio::getAudioById( $item->background_audio)['file'];
            
            foreach ($item->buttonPointList as &$buttonPoint) {
                if($scale){
                    $buttonPoint->width = self::getScaledDimension($buttonPoint->width, $scale);
                    $buttonPoint->height = self::getScaledDimension($buttonPoint->height, $scale);
                    $buttonPoint->x = self::getScaledDimension($buttonPoint->x, $scale);
                    $buttonPoint->y = self::getScaledDimension($buttonPoint->y, $scale);
                }
                $buttonBackgroundId = $buttonPoint->background_id;
                $buttonPointImageUrl = Image::getImageUrlById($buttonBackgroundId);
                $buttonPoint->imageUrl = $scale ? $buttonPointImageUrl . '?scale=' . $scale : $buttonPointImageUrl;
                $buttonPoint->localizationText;
                if($scale){
                    $buttonPoint->localizationText->width = self::getScaledDimension($buttonPoint->localizationText->width, $scale);
                    $buttonPoint->localizationText->height = self::getScaledDimension($buttonPoint->localizationText->height, $scale);
                    $buttonPoint->localizationText->x = self::getScaledDimension($buttonPoint->localizationText->x, $scale);
                    $buttonPoint->localizationText->y = self::getScaledDimension($buttonPoint->localizationText->y, $scale);
                    $buttonPoint->localizationText->size = self::getScaledDimension($buttonPoint->localizationText->size, $scale);
                }
                if ($buttonPoint->button_type === 1) {
                    $buttonPoint->param;
                }
            }
        }
        return $panelTitle;
    }
    // 比例缩放
    private static function getScaledDimension($dimension, $scale)
    {
        return (int) round($dimension * $scale / 100);
    }
}
