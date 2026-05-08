<?php
namespace app\model\Panel\hint;

use think\Model;

class PanelHint extends Model
{
    protected $table = 'panel_hint';
    
    protected $pk = 'id';

    protected $type = [
        'id'                   => 'integer',
    ];

    // 隐藏字段
    protected $hidden = [ 'width', 'height', 'x', 'y', 'create_time', 'update_time'];
}
