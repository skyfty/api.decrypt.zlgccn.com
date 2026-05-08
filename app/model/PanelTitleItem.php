<?php

namespace app\model;

class PanelTitleItem extends BaseModel
{
    protected $table = 'panel_title_item';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'               => 'integer',
        'panel_title_id'   => 'integer',
        'background_id'    => 'integer',
        'button_type'      => 'integer',
        'multiLanguage'    => 'boolean',
        'sort'             => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['content', 'panel_title_id', 'background_id', 'create_time', 'update_time'];

    // protected $append = ['image_url'];

    // public function getImageUrlAttr()
    // {
    //     return Image::getImageUrlById($this->background_id);
    // }

    
    // 面板标题关联
    public function panelTitle()
    {
        return $this->belongsTo(PanelTitle::class, 'panel_title_id');
    }
    
    // 本地化文本关联
    public function localizationText()
    {
        return $this->hasOne(PanelTitleLocalizationText::class, 'panel_title_item_id');
    }
    
    // 开始参数关联
    public function param()
    {
        return $this->hasOne(PanelTitleStart::class, 'panel_title_item_id');
    }

    
}