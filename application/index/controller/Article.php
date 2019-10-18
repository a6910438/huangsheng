<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/8
 * Time: 11:28
 */

namespace app\index\controller;


use app\common\entity\Article as ArticleModel;
use service\IndexLog;
use think\Db;
use think\Request;

class Article extends Base
{
    //获取咨询
    public function articleList(Request $request)
    {
        $article = new ArticleModel;
        $list = $article->getList(2);
        IndexLog::write('咨询', '用户获取资讯列表');
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #公告详情
    public function articleDetail(Request $request)
    {
        $article_id = $request->get('article_id');
        if (!$article_id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        $article = new \app\common\entity\Article();
        $list = $article->getDetail($article_id);
        IndexLog::write('咨询', '用户获取资讯详情');
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #获取游戏攻略
    public function getGameArt(Request $request)
    {
        $game_types = $request->post('game_types');
        $list = \app\common\entity\Article::where('article_id', $game_types)->find();
        $article = new \app\common\entity\Article();
        // $list['content'] = $article->updImgUrl($list['content']);
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #获取服务条款 行业资讯 我的团队活动规则
    public function getArtInfo(Request $request)
    {
        $types = $request->post('types');
        $list = \app\common\entity\Article::where('article_id', $types)->find();
        $article = new \app\common\entity\Article();
        // $list['content'] = $article->updImgUrl($list['content']);
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);
    }


    #首页公告
    public function getIndexArt(Request $request)
    {
        $list = \app\common\entity\Article::where('article_id', 3)->find();
        $article = new \app\common\entity\Article();
        // $list['content'] = $article->updImgUrl($list['content']);
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #首页
    public function indexBanner()
    {
        $list = Db::table('banner')->select();
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #我的公会图片
    public function getImage()
    {
        $list = Db::table('image')->select();
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #发布公会公告
    public function addGuild(Request $request)
    {
        $uid = $this->userId;

        $guild = new Guild();
        $res = $guild->addGuild($guild , $uid , $request->post());
        if ($res){
            return json(['code' => 0, 'msg' => '发布成功']);
        }
        return json(['code' => 1, 'msg' => '发布失败']);


    }

    #获取公会公告
    public function getGuildList(Request $request){
        $uid = $this->userId;
        $page = $request->post('page');
        $limit = $request->post('limit');
        $guild = Guild::getTable();
        $list = GuildCansee::alias('gc')
            ->field('g.*,gc.is_see')
            ->leftJoin('guild g','gc.guild_id = g.id')
            ->where('g.status',1)
            ->where('gc.cansee_user_id',$uid)
            ->order('create_time desc')
            ->page($page)
            ->limit($limit)
            ->select();
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);


    }

    #获取公告详情
    public function getGuildInfo(Request $request){
        $gid = $request->post('guild_id');
        $uid = $this->userId;
        $list = Guild::where('id',$gid)->find();
        $upd = GuildCansee::where('cansee_user_id',$uid)->where('guild_id',$gid)->update(['is_see'=>1]);
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #是否有邀请码图片地址
    public function hasInvitePic(Request $request){
        $uid = $this->userId;
        $invite_pic = User::where('id',$uid)->value('invite_pic');
        if ($invite_pic) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $invite_pic]);

        }

        return json(['code' => 1, 'msg' => '暂无邀请图片,请生成']);
    }
    #生成邀请码图片地址
    public function baseToPic(Request $request){
        $image = $request->post('image');
        $uid = $this->userId;

        $imageName = "invite_code".$this->userId.'.png';
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }
        $path = "./uploads/invite_code/";
        if (!is_dir($path)){ //判断目录是否存在 不存在就创建
            mkdir($path,0777,true);
        }
        $imageSrc= $path."/". $imageName; //图片名字
        $list = file_put_contents($imageSrc, base64_decode($image));//返回的是字节数
        $imagepath = $path.$imageName;
        $ins = User::where('id' , $uid)->update(['invite_pic'=>$imagepath]);
        if ($ins) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $imagepath]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    public function getServiceInfo(){
        $list = ServiceInfo::select();
        if ($list) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);
    }


    public function getTeamWord(Request $request)
    {
        $info = WordsConfig::where('id',1)->value('value');
        if ($info) {

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $info]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

}