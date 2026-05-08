<?php

namespace app\model\Panel\chapter;
use think\Model;

class PanelChapterLocalizationText extends Model
{
    protected $table = 'panel_chapter_localizationText';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer',
        'panel_chapter_id'  => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['panel_chapter_id', 'create_time', 'update_time'];
    
    // 面板标题项关联
    public function panelChapter()
    {
        return $this->belongsTo(PanelChapter::class, 'panel_chapter_id');
    }
}