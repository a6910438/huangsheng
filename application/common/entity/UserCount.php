<?php
namespace app\common\entity;

use think\Model;

class UserCount extends Model
{
    protected $table = 'user_count';

    protected $autoWriteTimestamp = false;

    public static function addData($userId, $total, $rate)
    {
        //判断是否存在
        $model = new self();
        $model->user_id = $userId;

        $model->total = $total;
        $model->rate = $rate;

        return $model->save();
    }

    public function myUpdate($total,$rate)
    {
        $this->total = $total;
        $this->rate = $rate;

        return $this->save();
    }

    public function check($userId)
    {
        $model = self::where('user_id', $userId)->find();
        if (!$model) {
            return false;
        }
        return $model;
    }


}