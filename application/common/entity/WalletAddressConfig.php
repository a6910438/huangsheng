<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class WalletAddressConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'wallet_address_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //添加新数据
    public function addNew($query,$data)
    {
        $query->address = $data['address'];
        $query->types = $data['type'];
        $query->create_time = time();
        return $query->save();
    }
}
