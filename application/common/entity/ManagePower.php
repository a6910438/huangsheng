<?php
namespace app\common\entity;

use think\Model;

class ManagePower extends Model
{

    protected $table = 'manage_power';

    public $autoWriteTimestamp = false;

    public function getMenuId()
    {
        return $this->menu_id;
    }

    public static function checkPath($path)
    {
        $menu = self::where('path', $path)->find();
        return $menu ? true : false;
    }
}