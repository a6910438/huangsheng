<?php
namespace app\common\entity;

use think\Model;

class ManageUser extends Model
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    /**
     * @var string 对应的数据表名
     */
    protected $table = 'manage_user';

    protected $autoWriteTimestamp = true;

    public function getId()
    {
        return $this->id;
    }

    /**
     * 获取用户名
     */
    public function getName()
    {
        return $this->manage_name;
    }

    /**
     * 获取密码
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 获取密码盐
     */
    public function getPasswordSalt()
    {
        return $this->password_salt;
    }

    /**
     * 获取禁用时间
     */
    public function getForbiddenTime()
    {
        return $this->forbidden_time ? date('Y-m-d H:i:s', $this->forbidden_time) : 0;
    }

    /**
     * 判断是否被禁用
     */
    public function isForbiddened()
    {
        return $this->forbidden_time ? true : false;
    }

    /**
     * 获取创建时间
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * 是否是默认用户
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * 获取用户所属分组名称
     */
    public function getGroupName()
    {
        $groupIds = ManageUserGroup::getGroupsByUserId($this->getId());

        $groups = ManageGroup::whereIn('id', $groupIds)->select();

        $groupStr = "";

        foreach ($groups as $group) {
            $groupStr .= $group->getGroupName() . ',';
        }

        return substr($groupStr, 0, -1);
    }
}