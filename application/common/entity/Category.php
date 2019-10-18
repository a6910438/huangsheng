<?php

namespace app\common\entity;

use think\Model;
use think\Db;

class Category extends Model {

    protected $table = 'category';

    /**
     * 新增下级节点
     * 存储过程
      DELIMITER ;;
      CREATE DEFINER=`root`@`localhost` PROCEDURE `category_addSubChild`(IN prefid int, IN newfid int)
      BEGIN
      SELECT @myLeft := lft FROM category WHERE fid = prefid;
      UPDATE category SET rgt = rgt + 2 WHERE rgt > @myLeft;
      UPDATE category SET lft = lft + 2 WHERE lft > @myLeft;
      INSERT INTO category(fid, lft, rgt) VALUES(newfid, @myLeft + 1, @myLeft + 2);
      END
      ;;
      DELIMITER ;
     * @param int $newfid      新（下级）节点id
     * @param int $prefid         上级节点id，默认为0（一级）
     */
    public function addSubChild($newfid, $prefid = 0) {
        //首先判断prefid是否存在，若不存在，就添加为 0 的下级节点
        if ($prefid != 0 && !Category::where('fid', $prefid)->find()) {
            Db::query('call category_addSubChild(:prefid,:newfid)',['prefid' => 0, 'newfid' => $prefid]);
        }
        return Db::query('call category_addSubChild(:prefid,:newfid)',['prefid' => $prefid, 'newfid' => $newfid]);
    }

    /**
     * 添加同级节点
     * 存储过程
      DELIMITER ;;
      CREATE DEFINER=`root`@`localhost` PROCEDURE `category_addSameLevlChild`(IN samelevelfid int, IN newfid int)
      BEGIN
      SELECT @myRight := rgt FROM category WHERE fid = samelevelfid;
      UPDATE category SET rgt = rgt + 2 WHERE rgt > @myRight;
      UPDATE category SET lft = lft + 2 WHERE lft > @myRight;
      INSERT INTO category(fid, lft, rgt) VALUES(newfid, @myRight + 1, @myRight + 2);
      END
      ;;
      DELIMITER ;
     * @param type $newfid              新节点id
     * @param type $samelevelfid        同级节点id，不能是0
     * @return boolean
     */
    public function addSameLevlChild($newfid, $samelevelfid) {
        if ($samelevelfid == 0) {
            return false;
        }
        return Db::query('call category_addSameLevlChild(:samelevelfid,:newfid)',['samelevelfid' => $samelevelfid, 'newfid' => $newfid]);
    }

    /**
     * 删除节点
     * 存储过程
      DELIMITER ;;
      CREATE DEFINER=`root`@`localhost` PROCEDURE `category_deleteChild`(IN deletefid int, IN newfid int)
      BEGIN
      SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1 FROM category WHERE fid = deletefid;
      DELETE FROM category WHERE lft BETWEEN @myLeft AND @myRight;
      UPDATE category SET rgt = rgt - @myWidth WHERE rgt > @myRight;
      UPDATE category SET lft = lft - @myWidth WHERE lft > @myRight;
      END
      ;;
      DELIMITER ;
     * @param type $deletefid
     * @return type
     */
    public function deleteChild($deletefid) {
        return Db::query('call category_deleteChild(:deletefid)',['deletefid' => $deletefid]);
    }

    /**
     * 查看某个节点下的所有子节点
     * @param int $fid              节点id，默认是顶级节点0
     * @param int $startLimit       开始偏移量，默认为0
     * @param int $limit            每次取出多少个，默认为0，则取出所有
     * @return array                所有节点的id（fid）
     */
    public function getSubChild($fid = 0,$startLimit = 0,$limit = 0) {
        //排除自己? AND node.fid <> $fid
        $sql = "SELECT node.fid FROM category AS node,category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.fid = $fid ORDER BY node.lft";
        if($limit > 0){
            $sql .= " LIMIT $startLimit,$limit";
        }
        $res = Db::query($sql);
        return array_column($res, 'fid');
    }

    /**
     * 获取所有叶子节点(最下面一级)
     * @return array     返回fid
     */
    public function getLeafChild() {
        $sql = "SELECT fid FROM category WHERE rgt = lft + 1";
        $res = Db::query($sql);
        return array_column($res, 'fid');
    }

    /**
     * 获取某个节点的所有父级fid
     * @param type $fid
     */
    public function getParentIds($fid) {
        if ($fid == 0) {
            return [0];
        }
        $sql = "SELECT parent.fid FROM category AS node,category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.fid = $fid ORDER BY parent.lft";
        $res = Db::query($sql);
        return array_column($res, 'fid');
    }

    /**
     * 获取某个节点下的节点深度
     * @param type $fid         $fid 为第一级 0
     * @param type $depth       获取深度，默认返回所有
     * @param type $onlyFid     是否只返回fid，若为 false 则返回fid 和 depth深度
     * @param int $startLimit   开始偏移量，默认为0
     * @param int $limit        每次取出多少个，默认为0，则取出所有
     * @return type
     */
    public function getChildDepth($fid = 0, $depth = 0,$onlyFid = true,$startLimit = 0,$limit = 0) {
        if ($fid == 0) {
            $sql = "SELECT node.fid, (COUNT(parent.fid) - 1) AS depth FROM category AS node,category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt GROUP BY node.fid ORDER BY node.lft";
        } else {
            $sql = "SELECT node.fid, (COUNT(parent.fid) - (sub_tree.depth + 1)) AS depth FROM category AS node,category AS parent,category AS sub_parent,
                (SELECT node.fid, (COUNT(parent.fid) - 1) AS depth FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt
                AND node.fid = $fid GROUP BY node.fid ORDER BY node.lft )
                AS sub_tree WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
                AND sub_parent.fid = sub_tree.fid GROUP BY node.fid";
            if ($depth > 0) {
                $sql .= " HAVING depth <= $depth";
            }
            $sql .= ' ORDER BY node.lft';
        }
        if($limit > 0){
            $sql .= " LIMIT $startLimit,$limit";
        }
        $res = Db::query($sql);
        if($onlyFid){
            return array_column($res, 'fid');
        }else{
            return $res;
        }
    }

    public function test() {
        //sql查看深度（直接运行）
        $sql = "SELECT CONCAT( REPEAT('-', COUNT(parent.fid) - 1), node.fid) AS fid
FROM category AS node,
category AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt
GROUP BY node.fid
ORDER BY node.lft;";
    }

}
