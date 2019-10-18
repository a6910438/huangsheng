<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\FomoConfig;
use app\common\entity\Buykey;
use app\common\entity\FomoGame;




class Divide  extends Model
{
    protected $table = 'fomo_divide';


    /*
      更新资金池
    */
    public function upDivide($orderid)
    {

        if(empty($orderid)){
            return;
        }

        //查看
        $Buykeylist = Buykey::where('id',$orderid)->find();


        if(!empty($Buykeylist)){
             //更新
            $this->setDivide('capital',$Buykeylist['capital'],$orderid);
            $this->setDivide('bonus',$Buykeylist['bonus'],$orderid);
            $this->setDivide('inviteaward',$Buykeylist['inviteaward'],$orderid);
            $this->setDivide('teamaward',$Buykeylist['teamaward'],$orderid);
            $this->setDivide('dropaward',$Buykeylist['dropaward'],$orderid);
        }

    }



    /*
      插入变动表
    */
    public function setDivide($types,$change,$orderid=0,$remark='')
    {

        if(empty($types) || empty($change)){
            return;
        }  


        $Fomolist = FomoGame::where('status',1)->order('id','desc')->find();

        //插记录
        Db::startTrans();
        try {

            $later = bcadd($Fomolist[$types],$change,8);

            //插入更新
            $arr = [
                'periods'=>$Fomolist['id'],
                'user_id'=>0,
                'types'=>$types,
                'orderid'=>$orderid,
                'change'=>$change,
                'before'=> $Fomolist[$types],
                'later'=> $later,
                'remark'=>$remark,
                'createtime'=>time()
            ];
            $Divide = new Divide();
            $result = $Divide->save($arr);

            if (!$result) {
                throw new \Exception('操作失败');
            }

           $FomoGame = new FomoGame();
           $FomoGame->where('id',$Fomolist['id'])->update(["{$types}"=>$later]);

           Db::commit();

        } catch (\Exception $e) {

            Db::rollback();
        }


    }




}
