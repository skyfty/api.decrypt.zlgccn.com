<?php

namespace app\model\Panel\loading;

use think\Model;

class PanelLoading extends Model
{
    protected $table = 'panel_loading';

    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'project_id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];

    public function PanelLoadingItems()
    {
        return $this->hasMany(PanelLoadingItem::class, 'panel_loading_id');
    }
 
}
