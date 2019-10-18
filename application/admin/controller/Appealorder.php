<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\AvatarLog;
use app\common\entity\Linelist;
use app\common\entity\Mywallet;
use app\common\entity\MywalletLog;
use app\common\entity\RechargeLog;
use app\common\entity\StoreLog;
use app\common\entity\TransferLog;
use app\common\entity\User as userModel;
use app\common\entity\Team as teamModel;
use app\common\entity\UserInviteCode;
use app\common\entity\UserMagicLog;
use app\common\entity\Export;
use app\common\entity\Withdraw;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use app\common\service\Users\Identity;
use think\Db;
use think\Request;
use service\LogService;
use think\Session;

class Appealorder extends Admin {

    /**
     * @power 产品管理|申诉列表
     * @rank 1
     */
    public function index(Request $request) {
        $entity = Db::table('appeal')
            ->alias('a')
            ->join('user u','u.id = a.uid')
            ->join('fish_order fo','fo.id = a.order_id')
            ->join('fish f','f.id = fo.f_id')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->join('appointment_user au','au.oid = fo.id')
            ->join('user fu','fu.id = f.u_id')
            ->join('user bu','bu.id = au.uid')
        ;

        $entity->field('a.id,a.create_time,a.status,a.content,u.nick_name uname,fo.order_number,fo.status fo_status,bp.name f_name,fu.nick_name fname,bu.nick_name bname,fo.worth');
        $entity->where('a.status', '>','-2');
        $type = $request->get('type');
        $keyword = $request->get('keyword');
        if ($keyword || $type) {
            switch ($type) {

                case 'mobile':
                    break;
                case 'statusf1':
                    $entity->where('a.status', '-1');

                    break;

                case 'status0':
                    $entity->where('a.status', '0');
                    break;
                case 'status1':
                    $entity->where('a.status', '1');
                    break;
            }

            if($keyword){
                $entity->where('a.mobile', $keyword);
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $orderStr = 'a.create_time DESC';

        $list = $entity
            ->order($orderStr)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

//        echo $entity->getLastSql();exit;
        $query = new \app\common\entity\Team();
        return $this->render('index', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }



    /**
     * 通过
     * @method get
     */
    public function activation(Request $request)
    {
        $id = $request->param('id');
        $entity = Db::table('appeal')
            ->alias('a')
            ->join('fish_order fo','fo.id = a.order_id')
            ->where('a.id',$id);
        $fid =  $entity->value('fo.f_id');

        $is_up = $this->adopt($fid,$id);
        LogService::write('产品管理', '通过申诉');

        if($is_up){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);

    }
    /**
     * 驳回
     * @method get
     */
    public function freeze(Request $request)
    {
        $id = $request->param('id');


        $entity = Db::table('appeal')
            ->alias('a')
            ->join('fish_order fo','fo.id = a.order_id')
            ->where('a.id',$id);
        $fid =  $entity->value('fo.f_id');

        $is_up = $this->up_fish($fid,$id,2);//恢复酒的状态

        if($is_up){
            LogService::write('产品管理', '驳回申诉');
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * 删除
     * @method get
     */
    public function delete(Request $request)
    {
        $id = $request->param('id');

        $entity = Db::table('appeal')
            ->alias('a')
            ->join('fish_order fo','fo.id = a.order_id')
            ->where('a.id',$id);
        $fid =  $entity->value('fo.f_id');

        $is_up = $this->up_fish($fid,$id,1);//恢复酒的状态



        if($is_up){
            LogService::write('产品管理', '删除申诉');
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    /**
     * 驳回/删除 恢复酒购买状态
     * @param $fid
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function up_fish($fid,$id,$type){


        if(!$fid){
            return json(['code' => 1, 'message' => '缺失参数!']);
        }



        $map['f.id'] = $fid;
        $map['f.order_id'] = ['>',0];
//        $map['f.u_id'] = $uid;

        $is_fish =  Db::table('fish')
            ->alias('f')
            ->join('user u','f.u_id = u.id')
            ->where($map)
            ->field('u.id uid,f.id fid,f.worth,f.order_id,f.status')
            ->find() ;


        if(!$is_fish){
            return  false;
        }
        if($is_fish['status'] != -3 ){
            return  false;

        }


        Db::startTrans();
        try {



            $is_fo = Db::table('fish_order')
                ->where('id',$is_fish['order_id'])
                ->find();

            if(empty($is_fo)){
                Db::rollback();

                return  false;

            }


            //恢复酒
            $is_au = Db::table('appointment_user')
                ->where('id',$is_fo['types'])

                ->find();
            if(!$is_au){
                Db::rollback();

                return  false;

            }

            if($is_au['okpay_time']){
                $fupsave['is_show'] = 1;//显示

                $fosave['status'] = 2;//完成订单
                $save['f.status'] = 4;//完成转账
                $auup['status'] = 4;

            }else{
                $fosave['status'] = 1; //待确认
                $save['f.status'] = 3;
                $auup['status'] = 3;
            }

            $is_auup = Db::table('appointment_user')
                ->where('id',$is_fo['types'])
                ->update($auup);
            if(!$is_auup){
                Db::rollback();
                return  false;

            }

            $save['f.update_time'] = time();






            $is_save =  Db::table('fish')
                ->alias('f')
                ->join('user u','f.u_id = u.id')
                ->where($map)
                ->update($save);
            if(!$is_save){
                Db::rollback();

                return  false;

            }

            $fupsave['status'] = 0;
            $fupsave['update_time'] = time();


            $is_fup = Db::table('fish')->where('id',$is_au['new_fid'])->update($fupsave);
            if(!$is_fup){
                Db::rollback();

                return  false;

            }


            $is_fosave = Db::table('fish_order')
                ->where('id',$is_fish['order_id'])
                ->update($fosave);

            if(!$is_fosave){
                Db::rollback();

                return  false;

            }


            if($type == 1){//删除
                $res =  Db::table('appeal')->where('id',$id)->delete();
                if(!$res){
                    Db::rollback();
                    return  false;
                }

            }elseif ($type == 2){//驳回
                $res =  Db::table('appeal')->where('id',$id)->update(['status'=>-1]);
                if(!$res){
                    Db::rollback();
                    return  false;
                }
            }





            Db::commit();
//
            return true;

//
        } catch (\Exception $e) {
            Db::rollback();
            return  false;

        }
    }


    public function adopt($fid,$id){


        if(!$fid){
            return json(['code' => 1, 'message' => '缺失参数!']);
        }



        $map['f.id'] = $fid;
        $map['f.order_id'] = ['>',0];

        $is_fish =  Db::table('fish')
            ->alias('f')
            ->join('user u','f.u_id = u.id')
            ->where($map)
            ->field('u.id uid,f.id fid,f.worth,f.order_id,f.status')
            ->find() ;


        if(!$is_fish){
            return  false;
        }

        Db::startTrans();
        try {



            $is_fo = Db::table('fish_order')
                ->where('id',$is_fish['order_id'])
                ->find();

            if(empty($is_fo)){
                Db::rollback();

                return  false;

            }


            //冻结
            $is_au = Db::table('appointment_user')
                ->where('id',$is_fo['types'])

                ->find();
            if(!$is_au){
                Db::rollback();

                return  false;

            }



            $fosave['status'] = -1;//
            $save['f.status'] = -3;
            $auup['status'] = -4;
            $auup['update_time'] = time();


            $is_auup = Db::table('appointment_user')
                ->where('id',$is_fo['types'])
                ->update($auup);

            if(!$is_auup){
                Db::rollback();
                return  false;

            }

            $save['f.update_time'] = time();



            $is_save =  Db::table('fish')
                ->alias('f')
                ->join('user u','f.u_id = u.id')
                ->where($map)
                ->update($save);

            if(!$is_save){
                Db::rollback();

                return  false;

            }


            $fupsave['update_time'] = time();

            $fupsave['is_show'] = 0;
            $is_fup = Db::table('fish')->where('id',$is_au['new_fid'])->update($fupsave);

            if(!$is_fup){
                Db::rollback();

                return  false;

            }

            $fosave['update_time'] = time();
            $is_fosave = Db::table('fish_order')
                ->where('id',$is_fish['order_id'])
                ->update($fosave);

            if(!$is_fosave){
                Db::rollback();

                return  false;

            }

            $res =  Db::table('appeal')->where('id',$id)->update(['status'=>1]);

            if(!$res){
                Db::rollback();
                return  false;
            }


            Db::commit();

            return true;

//
        } catch (\Exception $e) {
            Db::rollback();
            return  false;

        }
    }



}
