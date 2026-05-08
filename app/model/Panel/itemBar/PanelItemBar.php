<?php

namespace app\model\Panel\itemBar;

use think\Model;

class PanelItemBar extends Model
{
    protected $table = 'panel_itemBar';

    protected $pk = 'id';

    protected $type = [
        'id'                   => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

    public function ItemBarItemSlot()
    {
        return $this->hasOne(PanelItemBarItemSlot::class, 'project_id', 'project_id');
    }
}
