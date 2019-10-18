<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class TitleLog extends Model
{


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'title_log';

    /**
     * 获取邀请码
     * @return mixed|string
     */
    public function getInviteCode($uid = 0)
    {
        if(empty($uid)){
            $uid = $this->id;
        }
        $data = Db::table('user_invite_code')->where('user_id', $uid)->value('invite_code');

        return $data ? $data : '异常';
    }


    /**
     * 获取禁用时间
     */
    public function getStime()
    {
        return $this->stime ? date('Y-m-d H:i:s', $this->stime) : 0;
    }


    /**
     * 获取禁用时间
     */
    public function getNtime()
    {
        return $this->ntime ? date('Y-m-d H:i:s', $this->ntime) : 0;
    }

}
