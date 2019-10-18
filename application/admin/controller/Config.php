<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use think\Request;

class Config extends Admin
{
    /**
     * @power 系统配置|网站配置
     * @rank 1
     */
    public function index()
    {
        return $this->render('index', [
            'list' => \app\common\entity\Config::where('type', 1)->where('status',1)->select()
        ]);
    }

    /**
     * @power 系统配置|参数配置
     * @method GET
     */
    public function show()
    {
        return $this->render('show', [
            'list' => \app\common\entity\Config::where('type', 2)->where('status',1)->select()
        ]);
    }

    /**
     * @power 系统配置|交易配置
     * @method GET
     */
    public function market()
    {
        return $this->render('market', [
            'list' => \app\common\entity\Config::where('type', 3)->where('status',1)->select()
        ]);
    }

    /**
     * @power 系统配置|网站配置@修改配置
     */
    public function save(Request $request)
    {

        $key = $request->post('key');
        $value = $request->post('value');
        $config = \app\common\entity\Config::where('key', $key)->find();
        if (!$config) {
            throw new AdminException('操作错误');
        }
        $config->value = $value;
        if ($config->save() === false) {
            throw new AdminException('修改失败');
        }
        return ['code' => 0, 'message' => '配置成功'];
    }

    /**
     * @power 系统配置|日志列表
     * @method GET
     */
    public function logList(Request $request)
    {
        $entity = \app\common\entity\Log::field('*');
        if ($type = $request->get('type')) {
            $entity->where('type', $type);
            $map['type'] = $type;
        }
        $list = $entity->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $this->render('logList', [
            'list' => $list
        ]);
    }
    


}