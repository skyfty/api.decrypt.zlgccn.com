<?php

namespace app\model\Panel\update;

use think\Model;

class PanelUpdate extends Model
{
    protected $table = 'panel_update';

    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'project_id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];

    public function PanelUpdateItems()
    {
        return $this->hasMany(PanelUpdateItem::class, 'panel_update_id');
    }
 
}
