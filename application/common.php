<?php
use think\Db;
use app\common\entity\Config;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 手机号码归属地查询
 * @param $tel
 * @return string
 */
function getLocation($tel)
{
    // 过滤参数
    if ( !isPhoneNumber($tel) ) return ['code'=>200,'status'=>false,'msg'=>'Cell phone number error!'];

    // 请求地址
    $url = 'http://mobsec-dianhua.baidu.com/dianhua_api/open/location?tel='.$tel;

    // 发起请求
    //$res = file_get_contents($url);
    $res = curlRequest($url,'','GET');

    if ( $res['code'] !== 200 ) return ['code'=>$res['code'],'status'=>false,'msg'=>$res['responseHeader']['msg']];// 判断请求是否成功

    $data = $res['response'][$tel];// 接收返回值

    if ( !$data ) return ['code'=>200,'status'=>false,'msg'=>'API Exception!'];// 返回值为空

    $response['province'] = $data['detail']['province'];        // 归属地
    $response['city'] = $data['detail']['area'][0]['city'];   // 城市
    $response['service'] = $data['detail']['operator'];     // 运行商
    $response['fullname'] = $data['location'];            // 运行商全称

    return ['code'=>200,'status'=>true,'data'=>$response];
}

/**
 * 保留两位小数
 * @param int $num
 */
function retain_2($num=0 )
{
    return sprintf("%.2f",substr(sprintf("%.3f", $num), 0, -2));
}

/**
 * 手机号码格式验证
 * @param $tel
 * @return bool
 */
function isPhoneNumber($tel)//手机号码正则表达试
{
    return (preg_match("/0?(13|14|15|16|17|18|19)[0-9]{9}/",$tel))?true:false;
}

/**
 * 发起CURL请求
 * @param string $url 请求地址
 * @param string $data 请求数据
 * @param string $method 请求方式
 * @return array 一维数组
 */
function curlRequest($url,$data = '',$method = 'POST')
{
    $ch = curl_init(); //初始化CURL句柄
    curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而s不是直接输出
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式

    curl_setopt($ch,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: $method"));//设置HTTP头信息
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
    $document = curl_exec($ch);//执行预定义的CURL
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); //获取HTTP请求状态码～
    curl_close($ch);

    $document = json_decode(removeBOM($document),true);
    $document['code'] = $code;

    return $document;
}

/**
 * 检测并移除 BOM 头
 * @param string $str 字符串
 * @return string 去除BOM以后的字符串
 */
function removeBOM($str = '')
{
    if (substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
        $str = substr($str, 3);
    }
    return $str;
}


/**
 * 酒馆状态
 * @param int $key
 * @return mixed
 */
function pool_status($key = 0){
    $lits[0] = '生成中';
    $lits[1] = '预约';
    $lits[2] = '待领取';
    $lits[3] = '领取';
    $lits[4] = '排队中';
    $lits[5] = '酿酒中';
    $lits[6] = '已领取';
    $lits[7] = '即抢';
    $lits[8] = '待支付';
    $lits[9] = '待确认';

    $lits[51] = '测试';

    return $lits[$key];
}


/**
 * 银行列表
 * @return array
 */
function get_banklist(){
    $list = \think\Db::table('bank')->select();
    if($list){
        $list = array_column($list,'bank_name');
    }else{
        $list = array();
    }
    return $list;
}


/**
 * 个人日志
 * @param $name
 * @param $log
 */
function addMy_log($name,$log=0){
    $add['names'] = $name;
    if(empty($log)){
        $add['content'] = '';
    }else{
        $log = json_encode($log);
        $add['content'] = $log;
    }

    $add['log_time'] = time();
    \think\Db::table('my_log')->insert($add);
    \think\Db::table('my_log')->whereTime('log_time', '<','-100 day')->delete();

}

/**
 * 充值记录
 * @param $uid
 * @return mixed
 */
function get_addp($uid){
    return  \think\Db::table('my_wallet_log')
        ->where('uid',$uid)
        ->where('types','in','1')
        ->where('number','>',0)
        ->sum('number');
}

/**
 * 扣除记录
 * @param $uid
 * @return mixed
 */
function get_reducep($uid){
    return  \think\Db::table('my_wallet_log')
        ->where('uid',$uid)
        ->where('types','in','1,2,3')
        ->where('number','<',0)
        ->sum('number');
}

/*
 *
 *获得的酒价值
 */
function get_fishvalue($uid){
    return  \think\Db::table('fish_order')
        ->where('bu_id',$uid)
        ->where('status',2)
        ->sum('worth');
}

/**
 * 获取酒的装修开始时间
 * @param $id
 * @return mixed
 */
function get_fishstime($id){
    return  \think\Db::table('fish')
        ->where('id',$id)
        ->value('create_time');
}

/**
 * 获取酒龄(小时)
 * @param $id
 */
function get_fagetime($id){
    $stime = get_fishstime($id);
    $time = time() - $stime;
    $time = $time/3600;
    $time = (int)$time;
    return $time;
}

/**
 * 获取今天的key
 * @param $pid
 * @return string
 */
function get_today_key($pid){
    return strtotime(date('Y-m-d')).$pid;
}


/**
 * 获取用户昵称
 * @param $id
 * @return mixed|string
 */
function get_user_name($id){
    $name =  \think\Db::table('user')
        ->where('id',$id)
        ->value('nick_name');
    if(empty($name)){
        $name = '无';
    }
    return $name;
}

/**
 * 用户等级
 * @param int $key
 * @return string
 */
function user_lv_status($key = 0){

    switch ($key){
        case 1:
            $res = '初级';
            break;
        case 2:
            $res = '中级';
            break;
        case 3:
            $res = '高级';
            break;
        default:
            $res = '普通';
            break;
    }
    return $res;
}

/**
 * 用户激活状态
 * @param $status
 * @return string
 */
function getUserStatus($status)
{
    switch ($status) {
        case -1:
            return '禁用';
        case 0:
            return '未激活';
        case 1:
            return '激活';
        default:
            return '未知';
    }
}

/**
 * -4：申诉；-3:未及时支付; -2：超时未领取  -1分配失败； 0:参加预约  ;1：点击领取 2：分配到酒(定时任务派酒）   3:上传支付；4完成；
 * @param $status
 * @return string
 */
function getAUStatus($status)
{
    switch ($status) {
        case -4:
            return '申诉';
        case -3:
            return '未及时支付';
        case -2:
            return '超时未领取';
        case -1:
            return '分配失败';

        case 0:
            return '参加预约';
        case 1:
            return '点击领取';
        case 2:
            return '分配到酒';
        case 3:
            return '已传支付';
        case 4:
            return '完成订单';
        default:
            return '未知';
    }
}

function getadopt_lvStatus($status){
    switch ($status) {


        case 0:
            return '自由分配';
        case 1:
            return '第一区间';
        case 2:
            return '第二区间';
        case 3:
            return '第三区间';

        default:
            return '未知';
    }
}
/**
 * 酒状态 -3：投诉取消冻结； -2 :异常冻结（升级异常） ; -1： 冻结 0：正常 ； 1：等待预约；2待转账；3：转账中；4转账完成
 * @param $status
 * @return string
 */
function getFStatus($status)
{
    switch ($status) {

        case -3:
            return '投诉冻结';
        case -2:
            return '升级异常';
        case -1:
            return '冻结';
        case 1:
            return '点击领取';
        case 0:
            return '装修中';
        case 1:
            return '等待预约';
        case 2:
            return '待转账';
        case 3:
            return '转账中';
        case 4:
            return '转账完成';
        default:
            return '未知';
    }
}

/**
 * 积分状态
 * @param $status
 * @return string
 */
function getLtatus($status)
{
    switch ($status) {

        case 1:
            return '已解冻';
        case 2:
            return '未解冻';
        default:
            return '未知';
    }
}

/**
 * 所有预约数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getMakeAllNum($pid,$stime = 0,$ntime = 0){
    $entity = Db::table('appointment_user')
        ->where('pool_id',$pid)
		->where('types',0);
    if($stime){
        $entity->whereTime('create_time',[$stime, $ntime]);
    }
    return $entity->count('id');//所有

}

/**
 * 所有领取数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getAdoptAllNum($pid,$stime = 0,$ntime = 0){
    $entity = Db::table('appointment_user')
        ->alias('au')
        ->join('user u','u.id = au.uid')
        ->join('bathing_pool bp','bp.id = au.pool_id')
		->where('au.status','not in','0,-2')
        ->where('au.pool_id',$pid);

    if($stime){
        $entity->whereTime('au.create_time',[$stime, $ntime]);
    }
    return $entity->count('au.id');//所有

}

/**
 * 完成合约数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getContractAllNum($pid,$stime = 0,$ntime = 0){
    $entity = Db::table('fish')
        ->alias('f')
        ->join('fish_order fo','fo.id = f.order_id')

    ->where('f.pool_id',$pid)
        ->where('f.is_status','in','1')
		->where('f.status','in','4,-3');
    if($stime){
        $entity->where('fo.create_time','>',$stime);
        $entity->where('fo.create_time','<',$ntime);
    }
    return $entity->count('f.id');//所有

}

function getContractAllpoolNum($stime = 0,$ntime = 0){
    $entity = Db::table('fish')
        ->alias('f')
        ->join('fish_order fo','fo.id = f.order_id')
        ->where('f.is_status','in','1')
        ->where('f.status','in','4,-3');
    if($stime){
        $entity->where('fo.create_time','>',$stime);
        $entity->where('fo.create_time','<',$ntime);
    }
    return $entity->count('f.id');//所有

}
/**
 * 失败预约数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getMakeFaiNum($pid,$stime = 0,$ntime = 0){
    $entity = Db::table('appointment_user')
        ->where('pool_id',$pid);
    if($stime){
        $entity->whereTime('create_time',[$stime, $ntime]);
    }

    return $entity->where('status',-1)//失败
    ->count('id');

}

/**
 * 完成领取数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getMakeCompleteNum($pid,$stime = 0,$ntime = 0){
    $entity = Db::table('appointment_user')
        ->where('pool_id',$pid);
    if($stime){
        $entity->whereTime('create_time',[$stime, $ntime]);
    }

    return $entity->where('status',4)
        ->count('id');

}



/**
 * 转让数金额
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getMakeOverMoney($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_order')
        ->alias('fo')
        ->join('fish f','f.id = fo.f_id')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if(!empty($pid)){
        $entity ->where('bp.id',$pid);
    }else{
        $entity->where('bp.is_delete',0);
        $entity->where('bp.is_open',1);
    }
    $entity ->where('fo.status',2)
        ->where('fo.types','>',0);
    if($stime){
        $entity->whereTime('fo.create_time',[$stime, $ntime]);
    }
    return $entity->sum('fo.worth');//所有

}

/**
 * 转让数金额
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return float|int
 */
function getMakeOverNum($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_order')
        ->alias('fo')
        ->join('fish f','f.id = fo.f_id')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if(!empty($pid)){
        $entity ->where('bp.id',$pid);
    }else{
        $entity->where('bp.is_delete',0);
        $entity->where('bp.is_open',1);
    }
    $entity ->where('fo.status',2)
        ->where('fo.types','>',0);
    if($stime){
        $entity->whereTime('fo.create_time',[$stime, $ntime]);
    }
    return $entity->count('fo.id');//所有

}




/**
 * 预计转让数金额
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getMakeOverT0mMoney($pid = 0,$stime = 0,$ntime = 0){
    //非锁酒
    $unlock = Db::table('fish')
        ->alias('f')
        ->join('bathing_pool bp','bp.id = f.pool_id')
        ->where(['f.is_lock_num'=>0])
        ->where('bp.is_delete',0)
        ->where('bp.is_open',1)
        ->where('f.is_re',0)
        ->where('f.status',0)
        ->whereTime('f.feed_overtime',[$stime, $ntime])
        ->select();

    $unlock_num = 0;
    foreach($unlock as $k=>$v){
        $pool_time = Db::table('bathing_pool')
            ->where('id',$v['pool_id'])
            ->value('contract_time');
        $time = $v['contract_overtime'] + 24;
        if($time >= $pool_time){
            $unlock_num = $unlock_num+$v['worth'];
        }
    }
    //锁酒
    $lock = Db::table('fish')
        ->alias('f')
        ->join('bathing_pool bp','bp.id = f.pool_id')
        ->where('f.is_lock_num','>',0)
        ->where('bp.is_delete',0)
        ->where('bp.is_open',1)
        ->where('f.is_re',0)
        ->where('f.status',0)

        ->whereTime('f.feed_overtime',[$stime, $ntime])
        ->select();
    $lock_num = 0;
    foreach($lock as $k=>$v){
        $pool_time = Db::table('bathing_pool')->where('id',$v['pool_id'])->value('contract_time');
        $time = $v['lock_time'] + 24;
        if( $time>= $pool_time){
            $lock_num = $lock_num+$v['worth'];
        }
    }
    //返池酒
    $back = Db::table('fish')
        ->alias('f')
        ->join('bathing_pool bp','bp.id = f.pool_id')
        ->where(['f.is_lock_num'=>0,'f.is_re'=>1,'f.status'=>0,'f.is_status'=>2])
        ->where('bp.is_delete',0)
        ->where('bp.is_open',1)
        ->whereTime('f.feed_overtime',[$stime, $ntime])
        ->select();
    $back_num = 0;
    foreach($back as $k=>$v){
        $pool_time = Db::table('bathing_pool')->where('id',$v['pool_id'])->value('contract_time');
        $time = $v['contract_overtime'] + 24;
        if($time >= 24){
            $back_num = $back_num+$v['worth'];
        }
    }
    $total_num = $unlock_num + $lock_num + $back_num;
    return $total_num;

}

/**
 * 预计转让数金额
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return float|int
 */
function getMakeOverT0mNum($pid = 0,$stime = 0,$ntime = 0){
	
    $entity = Db::table('fish')
        ->alias('f')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if(!empty($pid)){
        $entity ->where('bp.id',$pid);
    }else{
        $entity->where('bp.is_delete',0);
        $entity->where('bp.is_open',1);
    }
    if($stime){
        $entity->whereTime('f.feed_overtime',[$stime, $ntime]);
    }
    return $entity->count('f.id');//所有
	

}


/**
 * 预计转让数金额2
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return float|int
 */
 function getMakeTomNum($pid = 0,$stime = 0,$ntime = 0){
	
	//非锁酒
	$unlock = Db::table('fish')
		->alias('f')
		->join('bathing_pool bp','bp.id = f.pool_id')
		->where(['f.is_lock_num'=>0])
		->where('bp.is_delete',0)
		->where('bp.is_open',1)
        ->where('f.is_re',0)
        ->where('f.status',0)
        ->whereTime('f.feed_overtime',[$stime, $ntime])
		->select();

	$unlock_num = 0;
	foreach($unlock as $k=>$v){
		$pool_time = Db::table('bathing_pool')
            ->where('id',$v['pool_id'])
            ->value('contract_time');
		$time = $v['contract_overtime'] + 24;
		if($time >= $pool_time){
			$unlock_num = $unlock_num+1;
		}
	}
	//锁酒
	$lock = Db::table('fish')
		->alias('f')
		->join('bathing_pool bp','bp.id = f.pool_id')
		->where('f.is_lock_num','>',0)
		->where('bp.is_delete',0)
		->where('bp.is_open',1)
		->where('f.is_re',0)
		->where('f.status',0)

		->whereTime('f.feed_overtime',[$stime, $ntime])
		->select();
	$lock_num = 0;
	foreach($lock as $k=>$v){
		$pool_time = Db::table('bathing_pool')->where('id',$v['pool_id'])->value('contract_time');
		$time = $v['lock_time'] + 24;
		if( $time>= $pool_time){
			$lock_num = $lock_num+1;
		}
	}
	//返池酒
	$back = Db::table('fish')
		->alias('f')
		->join('bathing_pool bp','bp.id = f.pool_id')
		->where(['f.is_lock_num'=>0,'f.is_re'=>1,'f.status'=>0,'f.is_status'=>2])
		->where('bp.is_delete',0)
		->where('bp.is_open',1)
		->whereTime('f.feed_overtime',[$stime, $ntime])
		->select();
	$back_num = 0;
	foreach($back as $k=>$v){
		$pool_time = Db::table('bathing_pool')->where('id',$v['pool_id'])->value('contract_time');
		$time = $v['contract_overtime'] + 24;
		if($time >= 24){
			$back_num = $back_num+1;
		}
	}
	$total_num = $unlock_num + $lock_num + $back_num;
	return $total_num;

}
 

/**
 * GTC数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return int|string
 * @throws \think\Exception
 */
function getBaitMoney($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_feed_log')
        ->alias('ffl')
        ->join('fish f','f.id = ffl.fid')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if($pid){
        $entity ->where('bp.id',$pid);
    }

    $entity->where('ffl.is_feed',1);

    $entity ->group('ffl.fid');

    if($stime&&$ntime){
        $entity->whereTime('ffl.stime',[$stime, $ntime]);
    }else{
        $date1 = date('Y-m-d');

        $stime = strtotime($date1);
        $ntime = strtotime("$date1 + 1 day ");
        $entity->whereTime('ffl.stime',[$stime, $ntime]);
    }
    $list = $entity->field('ffl.fid')
        ->select();
    $num = 0;

    if($list){
        foreach ($list as $k => $v){

            $num += Db::table('my_wallet_log')
                ->where('types',2)
                ->whereTime('create_time',[$stime, $ntime])
                ->where('from_id',$v['fid'])
                ->sum('number');
        }
        return abs($num);
    }else{
        return $num;
    }

}



/**
 * 装修酒数
 * @param $pid
 * @param int $stime
 * @param int $ntime
 * @return float|int
 */
function getBaitFishNum($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_feed_log')
        ->alias('ffl')
        ->join('fish f','f.id = ffl.fid')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if($pid){
        $entity ->where('bp.id',$pid);
    }

    $entity->where('ffl.is_feed',1);

    $entity ->group('ffl.fid');

    if($stime&&$ntime){
        $entity->whereTime('ffl.stime',[$stime, $ntime]);
    }else{
        $date1 = date('Y-m-d');

        $stime = strtotime($date1);
        $ntime = strtotime("$date1 + 1 day ");
        $entity->whereTime('ffl.stime',[$stime, $ntime]);
    }
    $list = $entity->field('ffl.fid')
        ->select();
    $num = 0;

    if($list){
        foreach ($list as $k => $v){

            $num += Db::table('my_wallet_log')
                ->where('types',2)
                ->whereTime('create_time',[$stime, $ntime])
                ->where('from_id',$v['fid'])
                ->count('id');
        }
        return abs($num);
    }else{
        return $num;
    }

}


function getBaitT0mMoney($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_feed_log')
        ->alias('ffl')
        ->join('fish f','f.id = ffl.fid')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if($pid){
        $entity ->where('bp.id',$pid);
    }

    $entity->where('ffl.is_feed',1);

    $entity ->group('ffl.fid');

    $entity->whereTime('ffl.stime',[$stime, $ntime]);

    $list = $entity->field('ffl.fid')
        ->select();
    $num = 0;

    if($list){

        return abs($num);
    }else{
        return $num;
    }

}



function getBaitYjMoney($pid = 0,$stime = 0,$ntime = 0,$type = 1){
    $entity = Db::table('fish_feed_log')
        ->alias('ffl')
        ->join('fish f','f.id = ffl.fid')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if($pid){
        $entity ->where('bp.id',$pid);
    }
    if($type == 1){
        $entity->where('ffl.types',1);
    }elseif ($type == 2){
        $entity->where('ffl.types',2);
    }else{
        $entity->where('ffl.types',3);
    }

    $entity ->group('ffl.fid');

    $entity->whereTime('ffl.stime',[$stime, $ntime]);

    $num  = $entity->sum('bp.bait');
    if($num > 0){
        $bp =  Db::table('bathing_pool')
            ->where('id',$pid)
            ->field('contract_time,profit,lock_position')
            ->find();
        if($type == 1){
            return $num;

        }elseif ($type == 2){

            return $num * $bp['lock_position'];

        }else{
            $day = $bp['contract_time'] / 24;
            if($day <=0){
                $day = 1;
            }
            return $num/$day;
        }
    }

}

function getAllBaitYjMoney($pid = 0,$stime = 0,$ntime = 0){
    $t1 = getBaitYjMoney($pid,$stime,$ntime,1);
    if($t1 < 0){
        $t1 = 0;
    }
    $t2 = getBaitYjMoney($pid,$stime,$ntime,2);
    if($t2 < 0){
        $t2 = 0;
    }
    $t3 = getBaitYjMoney($pid,$stime,$ntime,3);
    if($t3 < 0){
        $t3 = 0;
    }
    return $t1 + $t2 + $t3;
}



function getBaitFishYjNum($pid = 0,$stime = 0,$ntime = 0){
    $entity = Db::table('fish_feed_log')
        ->alias('ffl')
        ->join('fish f','f.id = ffl.fid')
        ->join('bathing_pool bp','bp.id = f.pool_id');
    if($pid){
        $entity ->where('bp.id',$pid);
    }



    $entity ->group('ffl.fid');

    $entity->whereTime('ffl.stime',[$stime, $ntime]);

    return $entity->count('bp.id');
}

//1:后台充值推广 2:前端推广 3.后台手动充值:
function getPlogtatus($status)
{
    switch ($status) {
        case 1:
            return '后台充值';
        case 2:
            return '交易生成';
        case 3:
            return '后台充值';
        case 4:
            return '抢酒失败返料';
        case 5:
            return '互转';
        case 6:
            return '即抢';
        case 7:
            return '支付';
        case 8:
            return '兑换';
        default:
            return '未知';
    }

}

/**
 * 1：平台操作 2.装修 3.预约 ；4.抢酒失败返料，5.互转 ，6即抢
 * @param $status
 * @return string
 */
function getWlogtatus($status)
{
    switch ($status) {

        case 1:
            return '后台充值';
        case 2:
            return '装修';
        case 3:
            return '预约';
        case 4:
            return '失败返料';
        case 5:
            return '互转';
        case 6:
            return '即抢';
        default:
            return '未知';
    }
}

/**
 * 计算酒售卖价值
 * @param $oid
 * @return int|mixed
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_fish_order_worth($oid){

    $map['au.oid'] = $oid;

    $msglist =  Db::table('appointment_user')
        ->alias('au')
        ->join('fish_order fo','au.id = fo.types','INNER')
        ->join('fish f','f.order_id = fo.id','INNER')
        ->join('bathing_pool bp','bp.id = f.pool_id','INNER')
        ->where($map)
        ->field('au.id,f.worth,f.front_id,f.types,f.front_worth,f.id f_id,f.is_re,f.u_id,fo.id fo_id,au.new_fid,bp.status bpstatus,bp.num')
        ->find();
    if($msglist['types'] == 1 || $msglist['types'] ==2){
        $tmpworth = Db::table('fish')->where('id',$msglist['front_id'])->value('worth');

        if($msglist['types'] == 1){
            //拆分


            $f_worth0 =bcdiv($tmpworth,$msglist['num'],2);

            $f_worth0 = (int)$f_worth0;

            if($msglist['is_re']){
                $f_worth1 = Db::table('fish_order')->where('id',$msglist['fo_id'])->value('worth');
            }else{
                $f_worth1 = Db::table('fish')->where('id',$msglist['new_fid'])->value('worth');
            }



        }else{
            //升级
            $f_worth0 =  $tmpworth;
            if($msglist['is_re']){
                $f_worth1 = Db::table('fish_order')->where('id',$msglist['fo_id'])->value('worth');
            }else {
                $f_worth1 = Db::table('fish')->where('id',$msglist['new_fid'])->value('worth');
            }
        }

    }else{
        $f_worth0 = Db::table('fish')->where('id',$msglist['f_id'])->value('worth');
        $f_worth1 = Db::table('fish_order')->where('id',$msglist['fo_id'])->value('worth');
    }

    $user_worth = $f_worth1 - $f_worth0;
    $user_worth = (int)$user_worth;
    $re['old'] = $f_worth0;
    $re['now'] = $f_worth1;
    $re['num'] = $user_worth;
    return $re ;
}

/**
 * 确认到期预计时间
 * @param $time
 * @return false|int
 */
function payment_time($time){
    $num = Config::getValue('auto_ok_order_time');
    $num = $num?$num:2;

    $date1 = date('Y-m-d H:i:s',$time);

    return date('Y-m-d H:i:s',strtotime("$date1 + $num hours "));//两小时前
}
function payment_zw_time($time){
    $num = Config::getValue('auto_ok_order_time');
    $num = $num?$num:2;

    $date1 = date('Y-m-d H:i:s',$time);

    return date('Y年m月d日H时i分s秒',strtotime("$date1 + $num hours "));//两小时前
}
/**
 * 装修过的次数
 * @param $id
 * @return int|string
 * @throws \think\Exception
 */
function get_feed_num($id,$contract_day,$num,$create_time = 0){
    $day = Db::table('fish_feed_log')
        ->where('is_feed','1')
        ->where('types',1)
        ->where('fid',$id)
        ->where('stime', '>=', $create_time)
        ->count('id');
    if($num > 1){
        $day = Db::table('fish_feed_log')
            ->where('is_feed','1')
            ->where('fid',$id)
            ->where('types',1)
            ->count('id');
        if($day > $contract_day){
            $day = $contract_day;
        }

        $day += Db::table('fish_feed_log')
            ->where('is_feed','1')
            ->where('fid',$id)
            ->where('types',2)
            ->count('id');

        $contract_day = $contract_day * $num;
    }

    if($day >= $contract_day){
        return $contract_day;
    }else{
        return $day;
    }

}

/**
 * 酒的类型
 * @param $fid
 * @return int
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_fish_type($fid){
    $is_f = DB::table('fish')
        ->alias('f')
        ->join('bathing_pool bp','bp.id = f.pool_id')
        ->where('f.is_delete','0')
        ->where('f.id',$fid)
        ->field('f.is_re,f.types,f.is_contract,f.contract_overtime,f.lock_overtime,f.all_time,f.is_lock_num,f.is_lock,f.is_status')
        ->find();


    if($is_f['types'] == 6 && $is_f['is_status'] == 1){
        if( $is_f['is_re'] == 1){
            if($is_f['is_status'] == 1){
                $types = 31;//重返酒馆
            }else{
                $types = 3;//重返酒馆
            }
        }else{
            $types = 4;//即卖
        }
    }elseif ($is_f['is_status'] == 2 || $is_f['is_re'] == 1){
        if($is_f['is_status'] == 1){
            $types = 31;//重返酒馆
        }else{
            $types = 3;//重返酒馆
        }
    }elseif($is_f['is_lock_num'] > 0 && $is_f['is_contract'] == 1 ){

        if($is_f['is_status'] == 1){
            $types = 21;//锁仓
        }else{
            $types = 2; //锁仓
        }

    }else{
        if($is_f['is_status'] == 1){
            $types = 11;
        }else{
            $types = 1;//合约养殖

        }
    }

    return $types;
}

/**
 * 是否装修
 * @param $fid
 * @return bool
 */
function is_feed($fid){
    $is_time = time();
    $feed =  Db::table('fish_feed_log')
        ->where('fid',$fid)
        ->where('stime','<',$is_time)
        ->where('ntime','>',$is_time)
        ->where('is_feed',1)
        ->order('ntime desc')
        ->value('ntime');

    if($feed){
        return true;
    }else{
        return false;
    }
}

/**
 * 创建文件夹
 * @param string $prefix
 * @param int $length
 * @return string
 */
function autoOrder($prefix = 'CK', $length = 8){
    $arr = array_merge(range('A','Z'), range(0, 9));
    $arrstr = $str = '';

    foreach ($arr as $v) $arrstr .= $v;

    for ($i=0; $i<$length; $i++) {
        $num = rand(0, strlen($arrstr));
        $str .= substr($arrstr, $num, 1);
    }
    return $prefix .date('Ymd'). $str;
}

/**
 * 获取房子的英文名
 *
 * @param string $name
 * @return void
 */
function getPoolEnName($name)
{
    switch ($name) {
        case '商品房':
            return 'Commercial Housing';
            break;
        
        case '公寓':
            return 'Apartment';
            break;
        
        case '草房':
            return 'Thatched Cottage';
            break;

        case '福利房':
            return 'Welfare Housing';
            break;

        case '平房':
            return 'Bungalow';
            break;

        case '商铺':
            return 'Shop';
            break;

        case '别墅':
            return 'Villa';
            break;

        default:
            return 'Test House';
            break;
    }
}