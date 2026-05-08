<?php

namespace app\model\Panel\dialogBox;

use think\Model;

class DialogBox extends Model
{
    protected $table = 'panel_dialog_box';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];


    public function dialogBoxOption()
    {
        return $this->hasOne(DialogBoxOption::class, 'project_id', 'project_id');
    }

    public function dialogBoxOptionItem()
    {
        return $this->hasOne(DialogBoxOptionItem::class, 'project_id', 'project_id');
    }
    
    public function dialogBoxRole()
    {
        return $this->hasOne(DialogBoxRole::class, 'project_id', 'project_id');
    }
    public function dialogBoxRoleName()
    {
        return $this->hasOne(DialogBoxRoleName::class, 'project_id', 'project_id');
    }


}
