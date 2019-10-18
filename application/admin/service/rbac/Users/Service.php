<?php
namespace app\admin\service\rbac\Users;


use app\admin\exception\AdminException;
use app\common\entity\ManageGroup;
use app\common\entity\ManagePower;
use app\common\entity\ManageUser;
use app\common\entity\ManageUserGroup;
use think\Config;
use think\Request;
use think\Session;

class Service
{
    /**
     * 加密前缀
     */
    const PREFIX_KEY = "admin";

    const SESSION_NAME = 'mysite_admin';

    const SALT_STRING = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * 生成加密盐
     */
    public function getPasswordSalt()
    {
        $saltLen = mt_rand(4, 10);
        $randStrLen = strlen(self::SALT_STRING);
        $string = "";
        for ($i = 1; $i <= $saltLen; $i++) {
            $string .= self::SALT_STRING[$randStrLen - 1];
        }
        return $string;
    }

    /**
     * 加密函数
     */
    public function getPassword($password, $passwordSalt)
    {
        return md5(md5(self::PREFIX_KEY . $password) . $passwordSalt);
    }

    /**
     * 验证密码
     */
    public function checkPassword($password, ManageUser $entity)
    {
        return $this->getPassword($password, $entity->getPasswordSalt()) === $entity->getPassword();
    }

    /**
     * 获取session
     */
    public function getManageInfo()
    {
        if (Session::has(self::SESSION_NAME)) {
            return Session::get(self::SESSION_NAME);
        }
        return [];
    }

    /**
     * 获取管理员id
     */
    public function getManageId()
    {
        $session = $this->getManageInfo();
        if(empty($session['id'])){
            return 0;
        }

        return $session['id'];
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete(self::SESSION_NAME);
    }

    /**
     * 登录处理
     */
    public function doLogin($accout, $password)
    {
        $userInfo = ManageUser::where('manage_name', $accout)->find();
        if (!$userInfo) {
            throw new AdminException('用户名或者密码错误');
        }
        if (!$this->checkPassword($password, $userInfo)) {
             return "用户名或者密码错误";
        }

        //设置session
        Session::set(self::SESSION_NAME, ['id' => $userInfo->getId(), 'name' => $userInfo->getName()]);

        return true;
    }

    /**
     * 检查用户名是否已存在
     */
    public function checkName($name, $id = 0)
    {
        $entity = ManageUser::where('manage_name', $name);
        if ($id) {
            $entity->where('id', '<>', $id);
        }
        return $entity->find() ? true : false;
    }

    /**
     * @return 检查权限
     */
    public function checkAuth()
    {
        $white = Config::get('app.rbac_white');

        $path = \app\admin\service\rbac\Power\Service::getRoutePath();

        $path = '/' . $path;
        $menus = $this->getMenus();
           
        if (in_array($path, $white) || in_array('all', $menus)) {
            return true;
        }

        $method = Request::method();

        $power = ManagePower::where('path', $path)->where('method', $method)->find();
           
        if (!$power) {
            return false;
        }
        if (!in_array($power->getMenuId(), $menus)) {
            return false;
        }

        return true;

    }

    /**
     * @获取菜单ids
     */
    public function getMenus()
    {
        $userId = $this->getManageId();

        $groupIds = ManageUserGroup::getGroupsByUserId($userId);

        $menuIds = [];
        $groups = ManageGroup::whereIn('id', $groupIds)->select();


        foreach ($groups as $group) {

            if ($group->getAuthIds() == 'all') {
                $menuIds = array_merge($menuIds, ['all']);
            } else {
                $menuIds = array_merge($menuIds, explode(',', $group->getAuthIds()));
            }
        }

        return array_unique($menuIds);
    }
}