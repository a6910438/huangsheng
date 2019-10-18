<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/7
 * Time: 11:10
 */

namespace app\admin\controller;
use app\admin\exception\AdminException;
use app\common\entity\Bonus;
use think\Db;
use think\Request;


class Deploy extends Admin
{
    #奖励列表
    public function bonus(){
        $result = Db::table('bonus')->order('id asc')->select();
        return $this->render('set',['list'=> $result]);
    }

    /**
     * @power 引荐人佣金奖励|添加设置
     * @method POST
     */
    public function setadd(Request $request)
    {
        $data['name'] = $request->post('name');
        $data['key'] = $request->post('key');
        $data['num'] = $request->post('num');
        $data['value'] = $request->post('value');
        $data['one'] = $request->post('one');
        $data['two'] = $request->post('two');
        $data['three'] = $request->post('three');
        $result = DB::table('bonus')->insert($data);
        if (!$result) {
            return ['code'=>1,'message'=>'添加失败'];
        }
        return ['code' => 0, 'message' => '添加成功'];
    }
    /**
     * @power 引荐人佣金奖励|更改设置
     * @method POST
     */
    public function setsave(Request $request)
    {
        $id = $request->post('id');
        $result = DB::table('bonus')->where('id',$id)->find();
        if (!$result) {
            throw new AdminException('操作错误');
        }
        $log = array(
            'value' => $request->post('value'),
            'num' => $request->post('num'),
            'one' => $request->post('one'),
            'two' => $request->post('two'),
            'three' => $request->post('three'),
        );
        $res = Db::table('bonus')->where('id',$id)->update($log);
//        var_dump($log);die;
        if(!$res){
            return ['code' => 1, 'message' => '修改失败'];
        }
        return ['code' => 0, 'message' => '修改成功'];
    }
    
}