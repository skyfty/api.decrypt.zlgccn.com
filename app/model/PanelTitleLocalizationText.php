<?php

namespace app\model;

class PanelTitleLocalizationText extends BaseModel
{
    protected $table = 'panel_title_localizationText';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer',
        'panel_title_item_id'  => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['panel_title_item_id', 'create_time', 'update_time'];
    
    // 面板标题项关联
    public function panelTitleItem()
    {
        return $this->belongsTo(PanelTitleItem::class, 'panel_title_item_id');
    }
}