<?php
namespace app\common\entity;

use think\Db;
use think\Model;

class LegalWalletLog extends Model
{
    protected $table = 'legal_wallet_log';

    public $autoWriteTimestamp = false;

    const TYPE_SYSTEM = 1; 

    public function getType($type)
    {
        switch ($type) {
            case self::TYPE_SYSTEM:
                return '系统';

        }
    }


    public static function addInfo($userId, $money_type,$number, $old, $new,$remark, $type = self::TYPE_SYSTEM)
    {
        
        $entity = new self();

        $entity->user_id = $userId;
        $entity->remark = $remark;
        $entity->number = $number;
        $entity->old = $old;
        $entity->new = $new;
        $entity->create_time = time();
        $entity->types = $type;
        $entity->money_type = $money_type;
            
        return $entity->save();
    }

    

    //查询账单
    public function magicloglist($type = '', $userId = '', $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $query = self::where('user_id', $userId)->field('*');
        if ($type == 1) {
            $query->where("magic", "GT", 0);
        } else {
            $query->where("magic", "LT", 0);
        }

        $list = $query->order("create_time desc")->limit($offset, $limit)->select();

        foreach ($list as $key => &$value) {
            $value['types'] = self::getType($value['types']);
        }

        return $list;
    }
}