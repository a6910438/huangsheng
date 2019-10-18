<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\Export;
use app\common\entity\Video;
use app\common\entity\WordsConfig;
use app\common\entity\ServiceInfo;
use service\LogService;
use think\Db;
use think\Request;
use app\common\entity\User;

class Notice extends Admin
{

    /**
     * @power 资讯管理|资讯列表
     * @rank 5
     */
    public function index(Request $request)
    {
        $entity = \app\common\entity\Article::field('*');

        if ($cate = $request->get('type')) {
            $entity->where('category', $cate);
            $map['cate'] = $cate;
        }

        $list = $entity->paginate(15, false, [
            'query' => isset($map) ? $map : []
        ]);

        return $this->render('index', [
            'list' => $list,
            'cate' => \app\common\entity\Article::getAllCate()
        ]);
    }

    /**
     * @power 内容管理|文章管理@添加文章
     */
    public function create()
    {
        return $this->render('edit', [
            'cate' => \app\common\entity\Article::getAllCate()
        ]);
    }

    /**
     * @power 内容管理|文章管理@编辑文章
     */
    public function edit($id)
    {
        $entity = \app\common\entity\Article::where('article_id', $id)->find();
        if (!$entity) {
            $this->error('用户对象不存在');
        }

        return $this->render('edit', [
            'info' => $entity,
            'cate' => \app\common\entity\Article::getAllCate()
        ]);
    }

    /**
     * @power 内容管理|文章管理@添加文章
     */
    public function save(Request $request)
    {
        $res = $this->validate($request->post(), 'app\admin\validate\Article');

        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }

        $service = new \app\common\entity\Article();
        $result = $service->addArticle($request->post());

        if (!$result) {
            throw new AdminException('保存失败');
        }
        //添加用户提醒
//        if($request->post('category')==1 && $request->post('status')==1){
//            User::update(['notice'=>1],['notice' => 0]);
//        }
        LogService::write('咨询管理', '用户添加咨询');
        return json(['code' => 0, 'toUrl' => url('/admin/Notice/index')]);
    }

    /**
     * @power 内容管理|文章管理@编辑文章
     */
    public function update(Request $request, $id)
    {

        $res = $this->validate($request->post(), 'app\admin\validate\Article');

        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }


        $entity = $this->checkInfo($id);

        $service = new \app\common\entity\Article();
        $result = $service->updateArticle($entity, $request->post());
        LogService::write('资讯管理', '用户编辑咨询');

        if (!$result) {
            throw new AdminException('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('/admin/notice/index')]);
    }

    /**
     * 导出留言
     */
    public function exportMessage(Request $request)
    {
        $export = new Export();
        $entity = \app\common\entity\Message::field('m.*,u.mobile, u.nick_name')->alias('m');
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile', $keyword);
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $list = $entity->leftJoin("user u", 'm.user_id = u.id')
            ->order('m.create_time', 'desc')
            ->select();
        $filename = '留言列表';
        $header = array('会员昵称', '会员账号', '内容', '提交时间');
        $index = array('nick_name', 'mobile', 'content', 'create_time');
        $export->createtable($list, $filename, $header, $index);
    }

    /**
     * @power 内容管理|文章管理@删除文章
     */
    public function delete(Request $request, $id)
    {
        $entity = $this->checkInfo($id);

        if (!$entity->delete()) {
            throw new AdminException('删除失败');
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    private function checkInfo($id)
    {
        $entity = \app\common\entity\Article::where('article_id', $id)->find();
        if (!$entity) {
            throw new AdminException('对象不存在');
        }

        return $entity;
    }

    /**
     * 视频添加
     */
    public function videoadd()
    {
        $info = Video::find();
        return $this->render('videoadd', [
            'info' => $info,
        ]);
    }

    /**
     * 视频保存
     */
    public function videoSave(Request $request)
    {
        $photo = $request->post('photo');
        $add_data = [
            'src' => $photo,
            'create_time' => time(),
        ];
        if (!$photo) return json(['code' => 1, 'message' => '请选择视频']);
        $list = Video::select();
        foreach ($list as $v) {

            if (file_exists('.' . $v['src'])) {
                unlink('.' . $v['src']);
            }
            Video::where('id', $v['id'])->delete();
        }

        $res = Video::insert($add_data);
        LogService::write('咨询管理', '用户添加视频');
        if ($res) {
            return json(['code' => 0, 'message' => '添加成功']);
        }
        return json(['code' => 1, 'message' => '添加失败']);

    }

}
