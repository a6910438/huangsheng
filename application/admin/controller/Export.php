<?php

namespace app\admin\controller;

use think\Request;
use think\Db;
use app\common\entity\GcWithdrawLog;

class Export extends Admin
{

    public function _initialize()
    {
        header("Content-type:text/html;charset=utf-8");
    }

    //导出csv文件
    public function put_csv($list, $title)
    {
        $file_name = "exam" . time() . ".csv";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $file_name);
        header('Cache-Control: max-age=0');
        $file = fopen('php://output', "a");
        $limit = 1000;
        $calc = 0;
        foreach ($title as $v) {
            $tit[] = iconv('UTF-8', 'GB2312//IGNORE', $v);
        }
        fputcsv($file, $tit);
        foreach ($list as $v) {
            $calc++;
            if ($limit == $calc) {
                ob_flush();
                flush();
                $calc = 0;
            }
            foreach ($v as $t) {
                $tarr[] = iconv('UTF-8', 'GB2312//IGNORE', $t);
            }
            fputcsv($file, $tarr);
            unset($tarr);
        }
        unset($list);
        fclose($file);
        exit();
    }

    /**
     * @power
     * @rank 1
     */
    public function export_moneydisplay(Request $request)
    {
        $type = $request->param('type');
        $case = $request->param('case');
        $keyword = $request->param('keyword');
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        try {

            if ($type == 2) {
                $proHibitLog = DB::table('prohibit_log')
                    ->alias('p')
                    ->leftJoin('user u', 'u.id=p.uid')
                    ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
                    ->field('concat(u.nick_name,\'（\',uic.invite_code,\'）\'),p.number,\'交易\',FROM_UNIXTIME(p.createtime),\'推广收益\'')
                    ->order('createtime', 'desc');

                switch ($case) {
                    case 'userid':
                        $proHibitLog->where('uic.invite_code', $keyword);
                        break;
                    case 'user_nick_name':
                        $proHibitLog->where('u.nick_name', $keyword);
                        break;
                    case 'mobile':
                        $proHibitLog->where('u.mobile', 'like', $keyword . '%');
                        break;
                }
                if ($startTime || $endTime) {
                    if (empty($startTime)) {
                        $startTime = time();
                    }
                    if (empty($endTime)) {
                        $endTime = time();
                    }
                    $proHibitLog->where('p.createtime', '<', strtotime($endTime))
                        ->where('p.createtime', '>=', strtotime($startTime));
                    $map['startTime'] = $startTime;
                    $map['endTime'] = $endTime;
                }
                $map['type'] = $type;
                $map['case'] = $case;
                $map['keyword'] = $keyword;
                $list = $proHibitLog->select();
                $result = ['list' => $list, 'map' => $map];
            } else if ($type == 3) {
                $result = $this->export_List('my_integral_log', $case, $keyword, $type, '积分', $startTime, $endTime);
            } else if ($type == 4) {

                $a = Db::name('my_wallet_log')->alias('m')
                    ->join('user u', 'u.id=m.uid')
                    ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
                    ->field('concat(u.nick_name,\'（\',uic.invite_code,\'）\'),m.number,m.remark,FROM_UNIXTIME(m.create_time) as create_time,\'GTC\'');
                $b = Db::name('my_integral_log')->alias('m')
                    ->field('concat(u.nick_name,\'（\',uic.invite_code,\'）\'),m.number,m.remark,FROM_UNIXTIME(m.create_time) as create_time,\'积分\'')
                    ->join('user u', 'u.id=m.uid')
                    ->leftJoin('user_invite_code uic', 'uic.user_id=u.id');
                switch ($case) {
                    case 'userid':
                        $a->where('uic.invite_code', $keyword);
                        $b->where('uic.invite_code', $keyword);
                        break;
                    case 'user_nick_name':
                        $a->where('u.nick_name', $keyword);
                        $b->where('u.nick_name', $keyword);
                        break;
                    case 'mobile':
                        $a->where('u.mobile', 'like', $keyword . '%');
                        $b->where('u.mobile', 'like', $keyword . '%');
                        break;
                }
                $map['type'] = $type;
                $map['case'] = $case;
                $map['keyword'] = $keyword;
                if ($startTime || $endTime) {
                    if (empty($startTime)) {
                        $startTime = time();
                    }
                    if (empty($endTime)) {
                        $endTime = time();
                    }
                    $a->where('m.create_time', '<', strtotime($endTime))
                        ->where('m.create_time', '>=', strtotime($startTime));
                    $b->where('m.create_time', '<', strtotime($endTime))
                        ->where('m.create_time', '>=', strtotime($startTime));
                    $map['startTime'] = $startTime;
                    $map['endTime'] = $endTime;
                }
                $list = Db::table($b->union([$a->buildSql()])->buildSql() . ' c')->order('c.create_time');
                $result = ['list' => $list->select()];
            } else if ($type == 5) {
                $result = $this->getListGc($case, $keyword, $type, $startTime, $endTime);
            } else {
                $result = $this->export_List('my_wallet_log', $case, $keyword, $type, 'GTC', $startTime, $endTime);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return true;
        }

//        return $this->render('moneyDisplay', $result);
        $csv_title = array('用户', '额度', '支付类型', '创建时间', '类别');
        $this->put_csv($result['list'], $csv_title);
    }


    /**
     * 封装查询扣款充值列表数据
     * @param $table
     * @param $case
     * @param $keyword
     * @param $type
     * @return array
     * @throws \think\exception\DbException
     */
    public function export_List($table, $case, $keyword, $type, $name, $startTime, $endTime)
    {
        // 页面刚打开时为空
        if (empty($type)) {
            $type = 1;
        }
        if (empty($keyword)) {
            $keyword = "";
        }
        $myWalletLog = Db::table($table)
            ->alias('m')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->field('concat(u.nick_name,\'（\',uic.invite_code,\'）\'),m.number,m.remark,FROM_UNIXTIME(m.create_time),\'' . $name . '\'')
            ->order('create_time desc');

        switch ($case) {
            case 'userid':
                $myWalletLog->where('uic.invite_code', $keyword);
                break;
            case 'user_nick_name':
                $myWalletLog->where('u.nick_name', $keyword);
                break;
            case 'mobile':
                $myWalletLog->where('u.mobile', 'like', $keyword . '%');
                break;
        }
        $map['type'] = $type;
        $map['case'] = $case;
        $map['keyword'] = $keyword;
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $myWalletLog->where('m.create_time', '<', strtotime($endTime))
                ->where('m.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $myWalletLog->select();
        return ['list' => $list, 'map' => $map];
    }

    //玩家充值详情
    public function recharge_detail(Request $request)
    {
        $id = $request->param('id');
        $map['id'] = $id;
        $where = array();
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if (empty($stime)) {
                $stime = time();
            } else {
                $stime = strtotime($stime);
            }
            if (empty($ntime)) {
                $ntime = time();
            } else {
                $ntime = strtotime($ntime);
            }
            if ($stime >= $ntime) {
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d', $stime);
            $map['ntime'] = date('Y-m-d', $ntime);
            $where = ['mwl.create_time' => ['between time', [$stime, $ntime]]];
        }
        $list = Db::table('my_wallet_log')
            ->alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->join('user_invite_code uic', 'uic.user_id = mwl.uid')
            ->where('mwl.uid', $id)
            ->where($where)
            ->order('mwl.create_time desc')
            ->field('uic.invite_code,u.lv,mwl.types,mwl.number,mwl.future,mwl.from_id,FROM_UNIXTIME(mwl.create_time) as create_time,mwl.remark')
            ->select();
        foreach ($list AS &$v) {
            $v['lv'] = user_lv_status($v['lv']);
            if ($v['types'] == 5) {
                $v['from_id'] = '="' . Db::table('user_invite_code')->where('user_id', $v['from_id'])->value('invite_code') . '"';
            } else {
                $v['from_id'] = '';
            }
            $v['create_time'] = '="' . $v['create_time'] . '"';
            if ($v['types'] == 7) {
                $v['types'] = $v['remark'];
            } else {
                $v['types'] = getWlogtatus($v['types']);
            }
            $v['remark'] = "";
        }
        $csv_title = array('玩家ID', '玩家身份', '操作类型', '操作数量', '剩余数量', '转让对象ID', '时间');
        $this->put_csv($list, $csv_title);
    }

    public function integral_detail(Request $request)
    {
        $where = array();
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if (empty($stime)) {
                $stime = time();
            } else {
                $stime = strtotime($stime);
            }
            if (empty($ntime)) {
                $ntime = time();
            } else {
                $ntime = strtotime($ntime);
            }
            if ($stime >= $ntime) {
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d', $stime);
            $map['ntime'] = date('Y-m-d', $ntime);
            $where = ['m.create_time' => ['between time', [$stime, $ntime]]];
        }
        $uid = $request->param('uid');
        $map['uid'] = $uid;
        $list = Db::table('my_integral_log')
            ->alias('m')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->where('u.id', $uid)
            ->where($where)
            ->order('create_time desc')
            ->field('uic.invite_code,u.lv,m.types,m.number,m.future,m.from_id,FROM_UNIXTIME(m.create_time) as create_time')
            ->select();
        foreach ($list AS &$v) {
            $v['lv'] = user_lv_status($v['lv']);
            if ($v['types'] == 5) {
                $v['from_id'] = '="' . Db::table('user_invite_code')->where('user_id', $v['from_id'])->value('invite_code') . '"';
            } else {
                $v['from_id'] = '';
            }
            $v['create_time'] = '="' . $v['create_time'] . '"';
            $v['types'] = getWlogtatus($v['types']);
        }
        $csv_title = array('玩家ID', '玩家身份', '操作类型', '操作数量', '剩余数量', '转让对象ID', '时间');
        $this->put_csv($list, $csv_title);
    }

    /**
     * GC提现导出数据
     *
     * @return void
     */
    public function export_withdraw(Request $request)
    {
        $type = $request->param('type');
        $keyword = $request->param('keyword');
        $startTime = $request->param('startTime');
        $endTime = $request->param('endTime');

        $where = [];
        // 关键字不为空则进行关键字过滤
        if (!empty($keyword)) {
            switch ($keyword) {
                case 'nick_name':
                    $where['u.nick_name'] = ['like', $keyword . '%'];
                    break;
            }
        }

        // 时间处理
        if (!empty($startTime) || !empty($endTime)) {
            if (!empty($startTime) && !empty($endTime)) {
                if ($startTime > $endTime) {
                    $where['gwl.create_time'] = ['>=', $endTime];
                } else {
                    $where['gwl.create_time'] = ['between', [$startTime, $endTime]];
                }
            } else {
                if (!empty($startTime)) {
                    $where['gwl.create_time'] = ['between', [$startTime, time()]];
                }
                if (!empty($endTime)) {
                    $where['gwl.create_time'] = ['>=', $endTime];
                }
            }
        }
        $where['gwl.status'] = ['in', [0, 1, 2]];
        //读取列表
        $list = GcWithdrawLog::alias('gwl')
            ->leftjoin('user u', 'gwl.uid=u.id')
            ->where($where)
            ->field([
                'gwl.id',
                'gwl.uid',
                'u.nick_name',
                'gwl.wallet_address',
                'gwl.total_amount',
                'gwl.status',
                'gwl.create_time'
            ])
            ->order('gwl.id desc')
            ->select();
        foreach ($list as &$v) {
            $v['uid'] = '（ID:' . $v['uid'] . '） ' . $v['nick_name'];
            if ($v['status'] == 0) {
                $v['status'] = '等待处理';
            } elseif ($v['status'] == 1) {
                $v['status'] = '提币成功';
            } else {
                $v['status'] = '请求驳回';
            }
            unset($v['nick_name']);
        }
        $list = collection($list)->toArray();
        $csv_title = array('ID', '用户', '钱包地址', '金额', '状态', '提交时间');
        $this->put_csv($list, $csv_title);
    }

    public function getListGc($case, $keyword, $type, $startTime, $endTime)
    {
        $myGcLog = DB::table('my_gc_log')
            ->alias('p')
            ->leftJoin('user u', 'u.id=p.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->field('concat(u.nick_name,\'（\',uic.invite_code,\'）\'),p.amount as number,p.remark,FROM_UNIXTIME(p.create_time),\'GC\'')
            ->order('create_time', 'desc');

        switch ($case) {
            case 'userid':
                $myGcLog->where('uic.invite_code', $keyword);
                break;
            case 'user_nick_name':
                $myGcLog->where('u.nick_name', $keyword);
                break;
            case 'mobile':
                $myGcLog->where('u.mobile', 'like', $keyword . ' % ');
                break;
        }
        $map['type'] = $type;
        $map['case'] = $case;
        $map['keyword'] = $keyword;
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $myGcLog->where('p.create_time', '<', strtotime($endTime))
                ->where('p.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $myGcLog->select();
        return ['list' => $list, 'map' => $map];
    }
}