<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\admin\service\rbac\Users\Service;
use app\common\entity\Dynamic_Log;
use app\common\entity\ManageUser;
use app\common\entity\Orders;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\Province;
use app\common\entity\UserProduct;
use think\Db;
use think\Model;
use think\Session;
use think\Request;
class Tongji extends Admin
{
    public function index(Request $request)
    {
        $entity=User::field('*');
        if($cate=$request->get('type')){
               $entity->where('province',$cate);
               $map['cate']=$cate;
        }
        $list=$entity->paginate(15,false,[
            'query'=>isset($map)?$map:[]
        ]);
            $invilid['status|is_active']=0;
        return $this->render('index',[
            'list'=>$list,
            'cate'=>User::getAllProvince(),
            'count'=>$entity->count(),
            'valid'=>$entity
                ->where('is_active','1')
                ->where('status','1')->count(),
            'invalid'=>$entity
                ->where($invilid)->count()
        ]);
    }

    public function huiyuan(){
        //会员数量
        $user['total'] = User::count();
        //有效会员数量
        $user['use_total'] = User::where('status',1)->count();
        //今日会员激活数量
        $user['register_user'] = User::where('status',1)->whereTime('register_time', 'today')->count();
        //今日会员注册数量
        $user['today'] = User::whereTime('register_time', 'today')->count();
        $data = [
            'user' => $user,
        ];
        return $this->render('huiyuan',$data);

    }
}