<?php
namespace app\support;

use think\facade\Db;

class ButtonPointBuilder
{
    public static function buildRow(int $type, array $param): array
    {
        $cfg = config("button_point.$type");
        if (!$cfg) return [];

        $row = [];
        foreach ($cfg['fields'] as $key => $rule) {
            $default = $rule['default'];
            $row[$key] = is_callable($default) ? $default($param) : ($param[$key] ?? $default);
        }
        return $row;
    }

    public static function sync(int $buttonPointId, int $type, array $param, ?int $subId = null): void
    {
        $cfg = config("button_point.$type");
        if (!$cfg || !$cfg['table']) return;   // ← 不要带值

        $data = self::buildRow($type, $param);
        $data['update_time'] = date('Y-m-d H:i:s');

        if ($subId) {
            Db::table($cfg['table'])->where('id', $subId)->update($data);
        } else {
            Db::table($cfg['table'])->insert(array_merge($data, [
                'button_point_id' => $buttonPointId,
                'create_time'     => date('Y-m-d H:i:s'),
            ]));
        }
    }

    public static function delete(int $buttonPointId, int $type): void
    {
        $cfg = config("button_point.$type");
        if (!$cfg || !$cfg['table']) return;   // ← 不要带值
        Db::table($cfg['table'])->where('button_point_id', $buttonPointId)->delete();
    }

    public static function subId(int $buttonPointId, int $type): ?int
    {
        $cfg = config("button_point.$type");
        if (!$cfg || !$cfg['table']) return null;
        return Db::table($cfg['table'])->where('button_point_id', $buttonPointId)->value('id');
    }
}