<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;

use app\common\entity\MywalletLog;
use app\common\entity\Mywallet;
use app\common\entity\Profit;
use app\common\entity\Proportion;
use app\common\entity\Quotation;
use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\WithdrawLog;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use app\index\model\Publics as PublicModel;
use app\index\model\Setup as SetupModel;
use think\Db;
use think\Request;
use service\IndexLog;

class Setup extends Base
{


    /**
     * 设置
     * @return \think\response\Json
     */
    public function index(){

        $uid = $this->userId;

        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);
        if($user_info){
            if($user_info['lv'] == 1){
                $name = '初级节点';

            }elseif($user_info['lv'] == 2){
                $name = '中级节点';
            }elseif($user_info['lv'] == 3){
                $name = '高级节点';
            }else{
                $name = '普通用户';
            }
            if(!$user_info['status']){
                $name .= '-未激活';
            }

            if($user_info['lv'] && $user_info['chat_num']){
                $user_info['is_team'] = 1;
            }else{
                $user_info['is_team'] = 0;
            }
            $user_info['extension'] = $user_info['prohibit_integral'];//总推广收益
            $user_info['now_extension'] = $user_info['now_prohibit_integral'];//推广收益

            $user_info['team'] = $user_info['team_integral'];//总团队收益
            $user_info['now_team'] = $user_info['now_team_integral'];//团队收益
            $user_info['chat_num'] = $user_info['chat_num'];//微信号
            $user_info['lv_name'] = $name;


            // if($user_info['pid']){
            //     $p_msg = Db::table('user')
            //         ->alias('u')
            //         ->join('user_invite_code uic','uic.user_id = u.id')
            //         ->where('u.id',$user_info['pid'])
            //         ->where('u.status',1)
            //         ->field('u.id,u.chat_num p_chat_num,u.nick_name p_nick_name,uic.invite_code p_invite_code')
            //         ->find();
            //     $user_info['p_chat_num'] = $p_msg['p_chat_num'];
            //     if(empty( $user_info['p_chat_num'] )){
            //         $user_info['p_chat_num'] = '无';
            //     }
            //     $user_info['p_invite_code'] = $p_msg['p_invite_code'];
            //     if(empty( $user_info['p_invite_code'] )){
            //         $user_info['p_invite_code'] = '无';
            //     }
            //     $user_info['p_nick_name'] = $p_msg['p_nick_name'];

            //     if(empty( $user_info['p_nick_name'] )){
            //         $user_info['p_nick_name'] = '无';
            //     }
            // }else{
            //     $user_info['pid'] = '无';
            //     $user_info['p_chat_num'] = '无';
            //     $user_info['p_nick_name'] = '无';
            //     $user_info['p_invite_code'] = '无';
            // }
            $entity = new \app\common\entity\MyWallet();
            $user_info['teamnum'] = $entity->teamnum($user_info['id']);//团队人数
            $user_info['pushnum'] = $entity->pushnum($user_info['id']);//直推人数
            $user_info['activationnum'] = $entity->pushactivationnum($user_info['id']);//激活人数
            $user_info['unactivationnum'] = $entity->pushunactivationnum($user_info['id']);//激活人数
            $user_info['invite_code'] =    (new User())->getInviteCode($user_info['id']);

            $housenum = DB::table('fish')
                ->alias('f')
//                ->join('appointment_user au', 'au.new_fid = f.id')
//                ->join('fish_order fo', 'fo.id = au.oid')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->where('bp.is_delete', '0')
                ->where('f.is_delete', '0')
                ->where('f.is_show', '1')
                ->where('f.status', 'in', '0,1,2,3')
                ->where('f.u_id', $uid)->count('f.id');

            $user_info['house_num'] = $housenum;
            return json(['code' => 0, 'msg' => '查询成功!','info' => $user_info]);

        }
        return json(['code' => 1, 'msg' => '无效用户']);


    }


    /**
     * 邀请码背景图
     * @return \think\response\Json
     */
    public function get_invitation_img(){
        $img = DB::table('invitationimg')->value('img');
        return json(['code' => 0, 'msg' => '查询成功','info'=>$img]);
    }

    /**
     * 修改社交号
     * @return \think\response\Json
     */
    public function chat_num_edit(){

        $uid = $this->userId;
        $chat_num = input('post.chat_num');
        if(!$chat_num){
            return json(['code' => 1, 'msg' => '微信不能为空']);

        }
        $chat_num = trim($chat_num);
        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);
        if($user_info){

            $is_save =  Db::table('user')->where('id',$uid)->update(['chat_num'=>$chat_num]);
            if($is_save){
                return json(['code' => 0, 'msg' => '成功!']);
            }

        }
        return json(['code' => 1, 'msg' => '失败']);


    }

    /**
     * 修改头像
     * @return \think\response\Json
     */
    public function avatar_edit(){

        $uid = $this->userId;
        $url = input('post.url');
        if(!$url){
            return json(['code' => 1, 'msg' => '头像不能为空']);

        }
        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);
        if($user_info){

          $is_save =  Db::table('user')->where('id',$uid)->update(['avatar'=>$url]);
          if($is_save){
              return json(['code' => 0, 'msg' => 'access!']);
          }

        }
        return json(['code' => 1, 'msg' => '失败']);


    }
    public function nick_name_edit(){

        $uid = $this->userId;
        $names = trim(input('post.names'));
        if(!$names){
            return json(['code' => 1, 'msg' => '昵称不能为空']);

        }
        $map['id'] = $uid;
        $is_user = Db::table('user')
            ->where($map)
            ->field('nick_name,id')
            ->find();


        if($is_user){

          $is_have =  Db::table('user')->where('id','<>',$uid)->where('nick_name',$names)->field('id')->find();

          if($is_have){
              return json(['code' => 1, 'msg' => '该昵称已被使用']);
          }
          $save['nick_name'] = $names;
          $save['update_time'] = time();

          $is_save =  Db::table('user')->where('id',$uid)->update($save);

          if($is_save){
              return json(['code' => 0, 'msg' => 'access!']);
          }

        }
        return json(['code' => 1, 'msg' => '失败']);


    }



    /**
     * 添加微信支付宝账号信息
     * @return \think\response\Json
     */
    public function AddReceivables(){

        $uid = $this->userId;

        $name = input('post.name');

        $types = input('post.types');
        $is_have = Db::table('card')->where('u_id',$uid)->where('types',$types)->where('is_delete',0)->find();
        if($is_have){
            return json(['code' => 1, 'msg' => '该类型账户已存在']);
        }


        if($types == 1){
            $add['bank_name'] = '微信';
        }else{
            $add['bank_name'] = '支付宝';

        }

        if(empty($name)){
            return json(['code' => 1, 'msg' => '用户名不能为空']);
        }

        $account_num = input('post.account_num');

        if(empty($account_num)){
            return json(['code' => 1, 'msg' => '账号不能为空']);
        }


        $imgs = input('post.imgs');

        if(empty($imgs)){
            return json(['code' => 1, 'msg' => '收款码不能为空']);
        }


        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);

        if(!$user_info){
            return json(['code' => 1, 'msg' => '无效用户']);
        }

        $add['u_id'] = $uid;
        $add['types'] = $types;
        $add['imgs'] = $imgs;
        $add['names'] = trim($name);
        $add['account_num'] = trim($account_num);
        $add['create_time'] = time();

        $is_add = Db::table('card')->insert($add);
        if($is_add){
            return json(['code' => 0, 'msg' => '添加成功!']);
        }

        return json(['code' => 1, 'msg' => '账户添加失败']);


    }


    public function AddReceivablesUp(){

        $uid = $this->userId;
        $is_no_pay = Db::table('fish')
            ->where('u_id',$uid)->where('status','in','2,3')->count('id');
        if($is_no_pay){
            return json(['code' => 1, 'msg' => '您还有未完成的订单，不得修改支付信息！']);
        }


        $id = input('post.id');
        if(empty($id)){
            return json(['code' => 1, 'msg' => '收款id不能为空']);
        }
        $name = input('post.name');

        $types = input('post.types');
        if($types == 1){
            $add['bank_name'] = '微信';
        }else{
            $add['bank_name'] = '支付宝';

        }

        if(empty($name)){
            return json(['code' => 1, 'msg' => '用户名不能为空']);
        }

        $account_num = input('post.account_num');

        if(empty($account_num)){
            return json(['code' => 1, 'msg' => '账号不能为空']);
        }


        $imgs = input('post.imgs');

        if(empty($imgs)){
            return json(['code' => 1, 'msg' => '收款码不能为空']);
        }


        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);

        if(!$user_info){
            return json(['code' => 1, 'msg' => '无效用户']);
        }

        $add['imgs'] = $imgs;
        $add['names'] = trim($name);
        $add['account_num'] = trim($account_num);
        $add['create_time'] = time();

        $is_add = Db::table('card')->where('id',$id)->where('u_id',$uid)->update($add);
        if($is_add){
            IndexLog::write('用户信息', '修改收款码',$uid);

            return json(['code' => 0, 'msg' => '修改成功!']);
        }

        return json(['code' => 1, 'msg' => '修改失败']);


    }




    public function AddReceivablesBank(){

        $uid = $this->userId;

        $name = input('post.name');

        $types = 2;

        $is_have = Db::table('card')->where('u_id',$uid)->where('types',$types)->where('is_delete',0)->find();
        if($is_have){
            return json(['code' => 1, 'msg' => '该类型账户已存在']);
        }

        if(empty($name)){
            return json(['code' => 1, 'msg' => '姓名不能为空']);
        }

        $account_num = input('post.account_num');

        if(empty($account_num)){
            return json(['code' => 1, 'msg' => '银行账号不能为空']);
        }

        $sub_branch = input('post.sub_branch');

        if(empty($sub_branch)){
            return json(['code' => 1, 'msg' => '支行不能为空']);
        }

        $bank_name = input('post.bank_name');

        if(empty($bank_name)){
            return json(['code' => 1, 'msg' => '银行名称不能为空']);
        }




        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);

        if(!$user_info){
            return json(['code' => 1, 'msg' => '无效用户']);
        }

        $add['u_id'] = $uid;
        $add['types'] = $types;
        $add['bank_name'] = trim($bank_name);
        $add['names'] = trim($name);
        $add['account_num'] = trim($account_num);
        $add['sub_branch'] = trim($sub_branch);
        $add['create_time'] = time();

        $is_add = Db::table('card')->insert($add);
        if($is_add){
            return json(['code' => 0, 'msg' => '添加成功!']);
        }

        return json(['code' => 1, 'msg' => '账户添加失败']);


    }


    public function AddReceivablesBankUp(){

        $uid = $this->userId;

        $is_no_pay = Db::table('fish')
            ->where('u_id',$uid)->where('status','in','2,3')->count('id');
        if($is_no_pay){
            return json(['code' => 1, 'msg' => '您还有未完成的订单，不得修改支付信息！']);
        }



        $id = input('post.id');

        $types = 2;

        if(empty($id)){
            return json(['code' => 1, 'msg' => '收款id不能为空']);
        }

        $name = input('post.name');


        if(empty($name)){
            return json(['code' => 1, 'msg' => '姓名不能为空']);
        }

        $account_num = input('post.account_num');

        if(empty($account_num)){
            return json(['code' => 1, 'msg' => '银行账号不能为空']);
        }

        $sub_branch = input('post.sub_branch');

        if(empty($sub_branch)){
            return json(['code' => 1, 'msg' => '支行不能为空']);
        }

        $bank_name = input('post.bank_name');

        if(empty($bank_name)){
            return json(['code' => 1, 'msg' => '银行名称不能为空']);
        }




        $SetupModel = new  SetupModel;

        $user_info = $SetupModel->user_info($uid);

        if(!$user_info){
            return json(['code' => 1, 'msg' => '无效用户']);
        }

        $add['bank_name'] = trim($bank_name);
        $add['names'] = trim($name);
        $add['account_num'] = trim($account_num);
        $add['sub_branch'] = trim($sub_branch);
        $add['update_time'] = time();

        $is_add = Db::table('card')->where('id',$id)->where('u_id',$uid)->update($add);

        if($is_add){
            IndexLog::write('用户信息', '修改收款码',$uid);

            return json(['code' => 0, 'msg' => '修改成功!']);
        }

        return json(['code' => 1, 'msg' => '账户修改失败']);


    }


    /**
     * 银行
     * @return \think\response\Json
     */
    public function get_banklist(){

        return json(['code' => 0, 'msg' => 'access!','info' => get_banklist()]);

    }

    /**
     * 支付账号列表
     * @return \think\response\Json
     */
    public function ReceivablesList(){

        $uid = $this->userId;
        $page = input('post.page')?input('post.page'):1;
        $limit = input('post.type')?input('post.limit'):15;

        $map['u_id'] = $uid;
        $map['is_delete'] = 0;
        $list = Db::table('card')
            ->where($map)
            ->field('id,bank_name,types,names,sub_branch,account_num')
            ->order('create_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();

        if(empty($list)){
            $list = array();
        }else{
            $list = $list['data'];

        }
        return json(['code' => 0, 'msg' => 'access!','info'=>$list]);
    }


    /**
     * 删除收款账户
     * @return \think\response\Json
     */
    public function ReceivablesDel(){
        $uid = $this->userId;
        $id = input('post.id');

        $map['id'] = $id;
        $map['u_id'] = $uid;
        $save['is_delete'] = 1;
        $save['delate_time'] = time();
        $save['update_time'] = time();
        $is_del = Db::table('card')->where($map)->update($save);
        if($is_del){
            return json(['code' => 0, 'msg' => 'access!']);
        }else{
            return json(['code' => 1, 'msg' => '删除失败!']);
        }
    }

    /**
     *
     * 获取收款信息
     * @return \think\response\Json
     */
    public function ReceivablesMsg(){
        $id = input('post.id');

        $map['id'] = $id;
        $map['is_delete'] = 0;

        $is_get = Db::table('card')->where($map)->find();
        if($is_get){
            return json(['code' => 0, 'msg' => 'access!','info'=>$is_get]);
        }else{
            return json(['code' => 1, 'msg' => '无效信息!']);
        }
    }

    /**
     * 收款信息列表
     * @return \think\response\Json
     */
    public function ReceivablesMsgList(){
        $id = input('post.id');

        $map['u_id'] = $id;
        $map['is_delete'] = 0;

        $is_get = Db::table('card')->where($map)->select();
        if($is_get){
            return json(['code' => 0, 'msg' => '查询成功!','info'=>$is_get]);
        }else{
            return json(['code' => 1, 'msg' => '无效信息!']);
        }
    }

}