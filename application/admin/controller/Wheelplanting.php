<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;

use app\common\entity\TransferLog;
use app\common\entity\ProductPool as ProductPoolModel;
use app\common\entity\Fish as FishModel;
use app\common\entity\User as userModel;
use app\common\entity\UserMagicLog;
use app\common\entity\Export;

use app\common\service;
use think\Db;
use think\Request;
use service\LogService;
use think\Session;

class Wheelplanting extends Admin {

    /**
     * @power 轮播图|产品列表
     * @rank 1
     */
    public function index(Request $request) {

        $entity = Db::table('wheelplanting')->where('is_delete',0);

        $list = $entity

            ->order('sort asc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);



        return $this->render('index', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }






    /**
     * @power 产品管理|列表@添加产品
     */
    public function create() {
        return $this->render('edit');
    }





    /**
     * 显示
     * @method get
     */
    public function activation(Request $request)
    {
        $id = $request->param('id');
        $res =  Db::table('wheelplanting')->where('id',$id)->update(['status'=>1,'update_time'=>time()]);
        LogService::write('幻灯片列表', '显示幻灯片');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * 隐藏
     * @method get
     */
    public function freeze(Request $request)
    {
        $id = $request->param('id');
        $res =  Db::table('wheelplanting')->where('id',$id)->update(['status'=>0,'update_time'=>time()]);
        LogService::write('幻灯片列表', '隐藏幻灯片');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }


    /**
     * @power 幻灯片列表|幻灯片列表@添加
     */
    public function save(Request $request) {

        $status = $request->post('status');
        $sort = $request->post('sort');
        $path = $request->post('path');
        $remarks= $request->post('remarks');
        $url=$request->post('url');
        if(empty($path)){
            return json()->data(['code' => 1, 'message' => '图片不能为空']);
        }
        if(empty($remarks)){
            return json()->data(['code' => 1, 'message' => '备注不能为空']);
        }


        $add['remarks'] = $remarks;
        $add['sort'] = $sort;
        $add['status'] = $status;
        $add['img'] = $path;
        $add['url']=$url;
        $add['update_time'] = time();
         $is_add =  Db::table('wheelplanting')->insert($add);
        if (!$is_add) {
            return json()->data(['code' => 1, 'message' => '保存失败']);
        }



        LogService::write('幻灯片列表', '添加幻灯片');
        return json(['code' => 0, 'toUrl' => url('index')]);

    }


    public function edit(Request $request)
    {
        $id = $request->param('id');
        $info = Db::table('wheelplanting')->where('id',$id)->find();

        return $this->render('edit',[
            'info' => $info,
        ]);
    }

    public function update(Request $request, $id) {
        $info = Db::table('wheelplanting')->where('id',$id)->find();
        if(!$info){
            return json()->data(['code' => 1, 'message' => '无效对象']);

        }
        $status = $request->post('status');
        $sort = $request->post('sort');
        $path = $request->post('path');
        $remarks= $request->post('remarks');

        if(empty($path)){
            return json()->data(['code' => 1, 'message' => '图片不能为空']);
        }
        if(empty($remarks)){
            return json()->data(['code' => 1, 'message' => '备注不能为空']);
        }


        $save['remarks'] = $remarks;
        $save['sort'] = $sort;
        $save['status'] = $status;
        $save['img'] = $path;
        $save['update_time'] = time();
        $is_add =  Db::table('wheelplanting')->where('id',$id)->update($save);




        LogService::write('幻灯片列表', '修改幻灯片');
        if (!$is_add) {
            return json(['code' => 1, 'message' => url('保存失败')]);
        }
        return json(['code' => 0, 'toUrl' => url('index')]);
    }



}
