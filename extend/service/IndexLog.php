<?php

// +----------------------------------------------------------------------
// | Service  
// +----------------------------------------------------------------------
// | 版权所有 
// +----------------------------------------------------------------------
// | 官方网站: http://www.xlkj16.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace service;

use app\index\controller\Base;
use think\Db;
use think\db\Query;
use think\Request;

/**
 * 操作日志服务
 * Class LogService
 * @package service
 * @date 2017/03/24 13:25
 */
class IndexLog
{

    /**
     * 获取数据操作对象
     * @return Query
     */
    protected static function db()
    {
        return Db::name('index_log');
    }

    /**
     * 写入操作日志
     * @param string $action
     * @param string $content
     * @return bool
     */
    public static function write($action = '行为', $content = "内容描述", $nick_name = null,$type = 0)
    {
        $request = Request::instance();

        $node = strtolower(join('/', [$request->module(), $request->controller(), $request->action()]));

        if(!$nick_name){

            $service = new Base();
            $info = $service->userInfo;
            $nick_name =$info->nick_name;

        }
        $data = [
            'ip'       => $request->ip(),
            'node'     => $node,
            'action'   => $action,
            'content'  => $content,
            'username' => $nick_name?$nick_name:'',
            'type' => $type,
            'http' => $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'',
        ];

        self::db()->whereTime('create_at', '<','-100 day')->delete();
        return self::db()->insert($data) !== false;
    }

}
