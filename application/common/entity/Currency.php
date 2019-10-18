<?php
namespace app\common\entity;

use think\facade\Cache;
use think\Model;

class Currency extends Model
{
    protected $table = 'currency';

    protected $autoWriteTimestamp = false;

    protected static $cacheKey = "web_wallet_config";

    public static function getValue($key)
    {
        $allConfig = self::getALLConfig();
        return isset($allConfig[$key]) ? $allConfig[$key] : '';
    }

    public static function getALLConfig()
    {
        $model = new self();
        $values = Cache::remember(self::$cacheKey, function () use ($model) {
            $list = $model->field('islegal,title')->select();
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

    public function editCurrency(currency $currency, $data)
    {
        
        $currency->title = $data['title'];
        $currency->islegal = $data['islegal'];
        // $article->status = $data['status'];
        // $article->sort = $data['sort'] ?? 0;
        // print_r($currency);
        return $currency->save();
    }
    
}