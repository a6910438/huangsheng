<?php
namespace app\index\model;

use think\Db;
use app\common\entity\User;
use app\common\entity\UserMagicLog;
use app\index\controller\Check;


class Checks
{
	
	// protected $type = [
 //        'balance'    =>  'float',
 //    ];

    public static function getmagic(){
    	
		// ignore_user_abort();//关闭浏览器后，继续执行php代码
	 //    set_time_limit(0);//程序执行时间无限制
	 //    $sleep_time = 15;//多长时间执行一次
	 //    $switch = include 'switch.php';


	 //    if($switch == 0){
	 //    	exit;
	 //    }

	 //    while($switch){
	        
	    	$user = User::field('trade_address,bth,id')->select();

	    	foreach ($user as $row) {


	    		$url = 'http://103.224.250.214/index.php/index/GetBalance?address='.$row['trade_address'];

	    		// $url = 'http://lll.weixqq4.top/web3/index/GetBalance?address=0x3f828ab9d55b277de7e584dd7a2387a9449fabad';
		        $ch = curl_init();
		        curl_setopt($ch, CURLOPT_URL, $url);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		        $balance = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
		        curl_close($ch);

		        // echo $balance;
	    		
	    		// // exit;

		        $magic = floatval($balance);
		        
		        // $magic = 1000000000;
	    		$l = 1000000000000000000; // 转化
	    		$magic = bcdiv($magic,$l,8);
		        $remark = '钱包充值';

		        if($magic > 0){

		        	$model = new UserMagicLog();
			        $result = $model->addInfo($row['id'], $remark, $magic, $row['bth'], $row['bth'] +  $magic, 1);

			        Db::table('user')->where('id', $row['id'])->update(['bth' => $row['bth'] +  $magic]);
			        echo '1';
			        echo '<hr>';
		        }else{
		        	continue;
		        }

		        // exit;
	    	}

	    //     sleep($sleep_time);//等待时间，进行下一次操作。
	    // }
	    // exit();

		// return ;
    }



}
