<?php
namespace app\common\entity;

use think\facade\Cache;
use think\Model;

class LegalConfig extends Model
{
    protected $table = 'legal_config';

    protected $autoWriteTimestamp = false;

    protected static $cacheKey = "web_legal_config";

    public static function getValue($key)
    {
        $allConfig = self::getALLConfig();
        return isset($allConfig[$key]) ? $allConfig[$key] : '';
    }

    public static function getALLConfig()
    {
        $model = new self();
        $values = Cache::remember(self::$cacheKey, function () use ($model) {
            $list = $model->field('key,value')->select();
            $data = [];
            foreach ($list as $item) {
                $data[$item->key] = $item->value;
            }
            return $data;
        });

        return $values;
    }

    public static function delCache()
    {
        Cache::rm(self::$cacheKey);
    }

    public function save($data = [], $where = [], $sequence = null)
    {
        self::delCache();
        return parent::save($data, $where, $sequence); 
    }

    
}