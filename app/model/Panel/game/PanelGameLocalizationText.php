<?php

namespace app\model\Panel\game;
use think\Model;

class PanelGameLocalizationText extends Model
{
    protected $table = 'panel_game_localizationText';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer',
        'panel_game_id'  => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = ['panel_game_id', 'create_time', 'update_time'];
    
    // 面板标题项关联
    public function panelTitleItem()
    {
        return $this->belongsTo(PanelGame::class, 'panel_game_id');
    }
}