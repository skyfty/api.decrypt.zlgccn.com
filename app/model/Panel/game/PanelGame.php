<?php

namespace app\model\Panel\game;

use think\Model;

class PanelGame extends Model
{
    protected $table = 'panel_game';

    protected $pk = 'id';

    protected $type = [
        'id'                   => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];


    public function localizationText()
    {
        return $this->hasOne(PanelGameLocalizationText::class, 'panel_game_id', 'id');
    }
}
