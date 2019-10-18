<?php
namespace app\common\service\Users;

use app\common\entity\User;


class Cache
{
    const CACHE_NAME = 'users_all';

    const CACHE_TTS = 300;

    public function getAllUsers()
    {
        $users = \think\facade\Cache::remember(self::CACHE_NAME, function () {
            //获取全部用户
            $users = User::field('id,pid')->select()->toArray();
            return $users;

        }, self::CACHE_TTS);

        return $users;
    }

    public function delCache()
    {
        \think\facade\Cache::rm(self::CACHE_NAME);
    }
}

