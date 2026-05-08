<?php

namespace app\model\globalConfig;

class ImageCategory extends \app\model\BaseModel
{
    protected $table = 'image_categories';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
        'name' => 'string',
        'type' => 'string',
        'project_id' => 'integer',
        'status' => 'integer',
    ];

    protected $hidden = [ 'project_id', 'create_time', 'update_time'];

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 创建时间字段名
    protected $createTime = 'create_time';

    // 更新时间字段名  
    protected $updateTime = 'update_time';
    
    // 按项目筛选
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}