<?php
namespace app\admin\service\rbac\Power;


use app\common\entity\ManageMenu;
use app\common\entity\ManagePower;
use think\Cache;
use think\Request;


class Service
{
    /**
     * 添加菜单
     */
    public static function addMenu($data)
    {
        $entity = new ManageMenu();
        $entity->path = $data['power'];
        $entity->level = $data['level'];
        $entity->sort = $data['sort'];
        $entity->parent_id = $data['parent_id'] ?? 0;
        if ($entity->save()) {
            return $entity->id;
        }
        return false;
    }


    /**
     * 添加 power
     */
    public static function addPower($path, $method, $meunId)
    {
        $entity = new ManagePower();
        $entity->path = $path;
        $entity->method = $method;
        $entity->menu_id = $meunId;

        return $entity->save();
    }

    /**
     * 获取请求route
     */
    public static function getRoutePath()
    {
        $request = Request::instance();
        $actions = $request->path();
        $arr = explode('/', $actions);
        if (count($arr) == 1) {
            $path = $actions . '/index/index';
        } elseif (count($arr) == 2) {
            $path = $actions . '/index';
        } else {
            $path = $actions;
        }

        return trim($path);
    }

    /**
     * 获取菜单
     */
    public function getMenus()
    {
        $session = new \app\admin\service\rbac\Users\Service();

        $cacheKey = 'user:menu:' . $session->getManageId();
        $model = new self();

        $value = Cache::remember($cacheKey, function () use ($session, $model) {
            $menuIds = $session->getMenus();

            if (in_array('all', $menuIds)) {
                $leftMenus = $model->getAllMenus();
            } else {
                $leftMenus = $model->getAllMenus($menuIds);
            }

            return $leftMenus;
        });

        return $value;
    }


    public function getAllMenus($menuIds = [])
    {

        $query = ManageMenu::where('manage_menu.level', 'in', '1,2');
        if ($menuIds) {
            $query->whereIn('manage_menu.id', $menuIds);
        }
        return $query->field('manage_power.path as url,manage_menu.*')
            ->join('manage_power', 'manage_power.menu_id=manage_menu.id','LEFT')
            ->order('manage_menu.sort', 'asc')
            ->select();
    }

    public function delCache()
    {
        $session = new \app\admin\service\rbac\Users\Service();
        $cacheKey = 'user:menu:' . $session->getManageId();
        Cache::rm($cacheKey);
    }

}
