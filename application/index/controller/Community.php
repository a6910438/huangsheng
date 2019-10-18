<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 14:29
 */

namespace app\index\controller;
use app\common\entity\ComReply;
use app\common\entity\ComSend;
use app\common\entity\ComZan;
use think\Request;
use think\db;
use app\common\entity\User;

class Community extends Base
{
    /**
     * @power 朋友圈|发表朋友圈
     */
    public function addCom (Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\ComSend');
        if ($validate !== true) {
            return json(['code' => 1, 'message' => $validate]);
        }
        $uid = $this->userId;
        $comsend = new ComSend();
        $result = $comsend->addSend($comsend,$request->post(),$uid);
        if($result) {

            return json(['code'=>0,'message'=>'发表成功']);
        }
        return json(['code'=>1,'message'=>'发表失败']);
    }
    /**
     * @power 朋友圈|评论朋友发的朋友圈
     */
    public function addReply (Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\ComReply');
        if ($validate !== true) {
            return json(['code' => 1, 'message' => $validate]);
        }
        $uid = $this->userId;
        $comReply = new ComReply();
        $result = $comReply->addReply($comReply,$request->post(),$uid);
        if ($result){
            return json(['code'=>0,'message'=>'评论成功']);
        }
        return json(['code'=>1,'message'=>'评论失败']);
    }

    #回复评论
    public function updReply(Request $request){
        $reply_id = $request->post('reply_id');
        $reply_content = $request->post('reply_content');
        $res = ComReply::where('id',$reply_id)->update(['reply_content'=>$reply_content,'update_time'=>time()]);
        if($res) return json(['code'=>0,'message'=>'评论成功']);
        return json(['code'=>1,'message'=>'评论失败']);
    }

    /**
     * @power 朋友圈|给朋友的朋友圈点赞
     */
    public function addClick (Request $request)
    {
        if(!$request->post('com_id')){
            return json(['code'=>1,'message'=>'缺少参数']);
        }
        $uid = $this->userId;
        $comZan = new ComZan();
        $isclick = $comZan->where('com_id',$request->post('com_id'))->where('user_id',$uid)->where('status',1)->find();
        if ($isclick){
            return json(['code'=>1,'message'=>'已点赞,请勿重复点击']);
        }

        $result = $comZan->addClick($comZan,$request->post(),$uid);
        if ($result){
            $res = ComSend::where('id',$request->post('com_id'))->setInc('zan',1);
        }
        if($res) return json(['code'=>0,'message'=>'点赞成功']);
        return json(['code'=>1,'message'=>'点赞失败']);
    }

    /**
     * @power 朋友圈|给朋友的朋友圈取消点赞
     */
    public function delClick (Request $request)
    {
        if(!$request->post('com_id')){
            return json(['code'=>1,'message'=>'缺少参数']);
        }
        $com_id = $request->post('com_id');
        $uid = $this->userId;
        $result = ComZan::where('com_id',$com_id)->where('user_id',$uid)->delete();
        if ($result){
            $res = ComSend::where('id',$request->post('com_id'))->setDec('zan',1);
            if($res) return json(['code'=>0,'message'=>'取消点赞成功']);
        }
        return json(['code'=>1,'message'=>'取消点赞失败']);
    }
    /**
     * @power 朋友圈|查看朋友圈列表
     */
    public function comList (Request $request)
    {
        $hotornew = $request->post('show_type');
        if ($hotornew == 1){
            $hotornew = 'view_num';
        }else{
            $hotornew = 'create_time';
        }
        $uid = $this->userId;
        $page = $request->post('page');
        $limit = $request->post('limit');
        $list = ComSend::alias('c')
            ->where('c.status',1)
            ->field('c.*,u.nick_name,u.avatar')
            ->leftJoin('user u','c.user_id = u.id')
            ->order("c.{$hotornew} desc")
            ->group('id')
            ->page($page)
            ->limit($limit)
            ->select();
        foreach ($list as &$v){
            $com_num = ComReply::where('com_id',$v['id'])->count();
            $v['com_num'] = $com_num;
            $zan = ComZan::where('user_id',$uid)->where('com_id',$v['id'])->where('status',1)->find();
            if ($zan){
                $v['is_zan'] = 1;
            }else{
                $v['is_zan'] = 0;
            }

        }

        return json(['code'=>0,'message'=>'请求成功','info'=>$list]);
    }
    /**
     * @power 朋友圈|查看朋友圈列表最热
     */
    public function comListHot (Request $request)
    {
        $uid = $this->userId;
        $page = $request->post('page');
        $limit = $request->post('limit');
        $list = ComSend::alias('c')
            ->where('c.status',1)
            ->field('c.*,u.nick_name,u.avatar,z.user_id as zan_uid ,z.status as zan')
            ->leftJoin('user u','c.user_id = u.id')
            ->leftJoin('community_zan z','z.com_id = c.id')
            ->order('c.view_num desc')
            ->page($page)
            ->limit($limit)
            ->select();
        foreach ($list as &$v){
            if ($v['zan_uid']  == $uid && $v['zan'] == 1){
                $v['is_zan'] = 1;
            }else{
                $v['is_zan'] = 0;
            }
        }

        return json(['code'=>0,'message'=>'请求成功','info'=>$list]);
    }

    #获取评论
    public function replyList(Request $request){
        $com_id = $request->post('com_id');
        $list = ComReply::alias('c')
            ->where('c.com_id',$com_id)
            ->field('c.*,u.nick_name,u.avatar')
            ->leftJoin('user u','c.user_id = u.id')
            ->order('create_time desc')
            ->select();
        return json(['code'=>0,'message'=>'请求成功','info'=>$list]);

    }

    #获取说说详情
    public function comInfo(Request $request){
        $uid = $this->userId;
        $com_id = $request->post('com_id');
        $list = ComSend::alias('c')
            ->where('c.id',$com_id)
            ->field('c.*,u.nick_name,u.avatar')
            ->leftJoin('user u','c.user_id = u.id')
            ->find();
        $com_num = ComReply::where('com_id',$com_id)->count();
        $res = ComSend::where('id',$request->post('com_id'))->setInc('view_num',1);
        $is_zan = ComZan::where('com_id',$com_id)->where('user_id',$uid)->value('status');
        return json(['code'=>0,'message'=>'请求成功','info'=>$list,'is_zan'=>$is_zan ,'com_num' => $com_num]);
    }

    #我发表的说说
    public function myList(Request $request){
        $uid = $this->userId;
        $page = $request->post('page');
        $limit = $request->post('limit');
        $list = ComSend::where('user_id',$uid)->where('status',1)->order('create_time desc')->page($page)->limit($limit)->select();
        return json(['code'=>0,'message'=>'请求成功','info'=>$list]);
    }

}