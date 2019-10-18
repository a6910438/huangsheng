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

class Appealuser extends Admin {

    /**
     * @power 会员管理|申诉列表
     * @rank 1
     */
    public function index(Request $request) {
        $entity = Db::table('appeal_user');

        $type = $request->get('type');
        $keyword = $request->get('keyword');
        if ($keyword || $type) {
            switch ($type) {

                case 'mobile':
                    break;
                case 'statusf1':
                    $entity->where('status', '-1');

                    break;
                case 'status0':
                    $entity->where('status', '0');
                    break;
                case 'status1':
                    $entity->where('status', '1');
                    break;
            }

            if($keyword){
                $entity->where('mobile', $keyword);
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $orderStr = 'create_time DESC';

        $list = $entity
                ->order($orderStr)
                ->distinct(true)
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
        $res =  Db::table('appeal_user')->where('id',$id)->update(['status'=>1]);
        LogService::write('会员管理', '通过申诉');
        if($res){
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
        $res =  Db::table('appeal_user')->where('id',$id)->update(['status'=>-1]);
        LogService::write('会员管理', '驳回申诉');
        if($res){
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
        $res =  Db::table('appeal_user')->where('id',$id)->delete();
        LogService::write('会员管理', '删除申诉');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }



}
