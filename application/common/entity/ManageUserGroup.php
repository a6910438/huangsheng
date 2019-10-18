<?php
namespace app\common\entity;

use think\Model;

class ManageUserGroup extends Model
{
    protected $table = 'manage_user_group';

    public $autoWriteTimestamp = false;

    public static function getGroupsByUserId($userId)
    {
        return self::where('user_id', $userId)->column('group_id');
    }

    public static function getUsersByGroupId($groupId)
    {
        return self::where('group_id', $groupId)->column('user_id');
    }

    public static function addInfo($userId, $groupId)
    {
        self::where('user_id', $userId)->delete();
        if (self::where('user_id', $userId)->where('group_id', $groupId)->find()) {
            return true;
        }

        $entity = new self();
        $entity->user_id = $userId;
        $entity->group_id = $groupId;

        return $entity->save();
    }

    public static function deleteBygroupId($groupId)
    {
        return self::where('group_id', $groupId)->delete();
    }
}