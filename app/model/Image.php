<?php

namespace app\model;

class Image extends BaseModel
{
    protected $table = 'image';

    protected $pk = 'id';

    protected $type = [
        'id'        => 'integer',
        'file'      => 'string',
    ];
    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

    /**
     * 直接查询（更高效）
     */
    public static function getImageUrlById($imageId, $scale = 100)
    {
        if (!$imageId) {
            return null;
        }

        $image = self::where('id', $imageId)->value('file');

        if (!$image) {
            return null;
        }

        // 根据 scale 参数添加后缀
        $concatPath = ($scale === 100 || $scale === 0) ? '' : "?scale={$scale}";
        return $image . $concatPath;
    }

    /**
     * 直接查询（更高效）
     */
    public static function getProjectImageUrlByCategory($project_id, $category_id)
    {
        if (!$project_id && !$category_id) {
            return [];
        }

        $images = self::where([
            'projectId' => $project_id,
            'category_id' => $category_id
        ])->select();

        if (!$images) {
            return [];
        }

        // 根据 scale 参数添加后缀
        return $images;
    }
}
