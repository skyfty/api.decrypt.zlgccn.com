<?php
// app/model/Project.php
namespace app\model;

use think\Model;


class Project extends Model
{
    protected $table = 'project';

    protected $hidden = [ 'user_id', 'status', 'create_time', 'update_time'];
    
    // 城市关联
    public function citys()
    {
        return $this->hasMany(City::class, 'project_id');
    }
    
    // 首页关联关联
    public function panelTitle()
    {
        return $this->hasMany(PanelTitle::class, 'project_id');
    }

}