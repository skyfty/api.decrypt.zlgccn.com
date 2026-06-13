<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Request;
use think\facade\Db;
use app\model\Image;
use app\model\Panel\chapter\PanelChapter;
use app\model\Panel\chapter\PanelChapterLocalizationText;

class Chapter
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $projectId = Request::param('project_id');


        $ChapterData = PanelChapter::where('project_id', $projectId)->select();

        // 如果没有数据误
        if (!$ChapterData || $ChapterData->isEmpty()) {
            // 假设你要检查项目中是否存在图片
            $image = Image::where('projectId', $projectId)->find();
            if (!$image) {
                return error('图片资源为空，先配置图片资源');
            }
            $param['project_id'] = $projectId;
            $createResult = $this->createChapter($param);

            if ($createResult) {
                // 重新查询新创建的数据
                $ChapterData = PanelChapter::with('localizationText')
                    ->where('project_id', $projectId)
                    ->select();

                return success($ChapterData, '该项目尚未配置，已自动创建默认配置.');
            } else {
                return error('该项目尚未配置，自动创建默认配置失败.');
            }
        }

        // 获取本地化文本
        $ChapterData = PanelChapter::with('localizationText')
            ->where('project_id', $projectId)
            ->select();

        return success($ChapterData);
    }

    /**
     * 保存更新游戏数据
     */
    public function save()
    {
        $param = Request::post();

        if (empty($param['project_id'])) {
            return error('关联项目ID 不能为空.');
        }

        try {
            Db::startTrans();

            if (!empty($param['id'])) {
                // 更新操作
                $result = $this->updateChapter($param);
            } else {
                // 创建操作
                $result = $this->createChapter($param);
            }

            Db::commit();

            if ($result) {
                $message = !empty($param['id']) ? '更新成功' : '创建成功';
                return success($result, $message);
            } else {
                return error('操作失败', 500);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return error('操作失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新游戏数据及对应的本地化文本
     */
    private function updateChapter(array $param)
    {
        $Chapter = PanelChapter::find($param['id']);
        if (!$Chapter) {
            return error('记录不存在');
        }

        $param['update_time'] = date('Y-m-d H:i:s');
        $ChapterSaveResult = $Chapter->save($param);

        if ($ChapterSaveResult) {
            // 更新或创建对应的本地化文本
            $this->updateOrCreateLocalizationText($param);
        }

        return $ChapterSaveResult;
    }

    /**
     * 更新或创建本地化文本
     */
    private function updateOrCreateLocalizationText($param)
    {
        $localizationText = PanelChapterLocalizationText::where('id', $param['localizationText']['id'])->find();

        // 更新现有记录
        $updateData = $param['localizationText'];
        $updateData['update_time'] = date('Y-m-d H:i:s');

        return $localizationText->save($updateData);
    }

    /**
     * 创建游戏数据及对应的本地化文本
     */
    private function createChapter(array $param)
    {

        // 根据原代码逻辑，创建两条记录
        $Chapters = [];

        // 第一条记录：type为hint
        $Chapter1 = new PanelChapter;
        $Chapter1->project_id = $param['project_id'];
        $Chapter1->width = $param['width'] ?? 250;
        $Chapter1->height = $param['height'] ?? 60;
        $Chapter1->x = $param['x'] ?? 0;
        $Chapter1->y = $param['y'] ?? 0;
        $Chapter1->save();

        // 创建对应的本地化文本
        $this->createLocalizationText($Chapter1->id, $param['localizationText'] ?? []);
        $Chapters[] = $Chapter1;

        // 返回创建的游戏数组
        return $Chapters;
    }

    /**
     * 创建本地化文本记录
     */
    private function createLocalizationText(int $panelChapterId, array $payload = [])
    {
        $localizationText = new PanelChapterLocalizationText;
        $localizationText->panel_chapter_id = $panelChapterId;
        $localizationText->width = $payload['width'] ?? 160;
        $localizationText->height = $payload['height'] ?? 40;
        $localizationText->x = $payload['x'] ?? 0;
        $localizationText->y = $payload['y'] ?? 0;
        $localizationText->content = $payload['content'] ?? '';
        $localizationText->color = $payload['color'] ?? '#ffffff';
        $localizationText->size = $payload['size'] ?? 16;
        $localizationText->save();

        return $localizationText;
    }

    public function delete()
    {
        $params = Request::param();
        $id = (int) ($params['id'] ?? 0);
        $projectId = (int) ($params['project_id'] ?? 0);

        if ($id <= 0 || $projectId <= 0) {
            return error('章节ID和项目ID不能为空', 400);
        }

        $chapter = PanelChapter::find($id);
        if (!$chapter) {
            return error('记录不存在', 404);
        }

        if ((int) $chapter['project_id'] !== $projectId) {
            return error('记录不属于当前项目', 400);
        }

        Db::startTrans();
        try {
            PanelChapterLocalizationText::where('panel_chapter_id', $id)->delete();
            PanelChapter::destroy($id);
            Db::commit();

            return success(['id' => $id], '删除成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('删除失败：' . $e->getMessage(), 500);
        }
    }

}
