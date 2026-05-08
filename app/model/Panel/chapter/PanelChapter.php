<?php

namespace app\model\Panel\chapter;

use think\Model;

class PanelChapter extends Model
{
    protected $table = 'panel_chapter';

    protected $pk = 'id';

    protected $type = [
        'id'                   => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];


    public function localizationText()
    {
        return $this->hasOne(PanelChapterLocalizationText::class, 'panel_chapter_id', 'id');
    }
}
