<?php
namespace app\common\entity;

use think\Model;

class Log extends Model
{
    const TYPE_PRODUCT = 1; //产品
    const TYPE_REMARKT = 2; //交易市场
    const TYPE_UPGRADE = 3; //升级错误
    const TYPE_INCOME = 4; //魔盒收益
    const TYPE_SHARE = 5; //全网分红
    const ADD_FISH = 6; //后台酒馆投放

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'log';

    protected $autoWriteTimestamp = false;

    protected $createTime = 'create_time';

    /** 写入日志
     * @param $type
     * @param $desc
     * @param $detail
     * @return false|int
     */
    public static function addLog($type, $desc, $detail)
    {
        $model = new self();
        $model->type = $type;
        $model->desc = $desc ?: '';
        $model->detail = is_array($detail) ? serialize($detail) : $detail;
        $model->create_time = time();

        return $model->save();
    }

    /**
     * 发生时间
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }
}