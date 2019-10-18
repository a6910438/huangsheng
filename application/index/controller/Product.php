<?php

namespace app\index\controller;

use app\admin\exception\AdminException;
use think\Request;
use app\common\service\Users\Service;
use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\UserProduct;
use app\common\entity\UserMagicLog;
use app\common\entity\UserJewelLog;
use app\common\entity\Product as productModel;
use think\Db;

class Product extends Base {

    public function index(Request $request) {
        return $this->fetch('index', [
            'list' => productModel::where('status', 1)->select(),
            'moneyName'=>Config::getValue("web_money_name")
        ]);
    }

    /**
     * 购买魔盒
     */
    public function recharge(Request $request) {
        $product_id = $request->post("product_id");
        //得到魔盒详细信息
        $productModel = new productModel();
        $product = $productModel->getInfoById($product_id);
        if (!$product) {
            return json(['code' => 1, 'msg' => '该茶园不存在']);
        }

        //获取用户详细信息
        $user = new \app\index\model\User();
        $userInfo = $user->getInfo($this->userId);

        //得到用户购买魔盒方式 1金币 2金条
        $type = $request->post("type");

        //判断用户账户金币数量是否足够支付购买该魔盒
        if (($userInfo['magic'] < $product['price']) && $type == 1) {
            $moneyName = Config::getValue('web_money_name');
            return json(['code' => 1, 'msg' => '您账户'.$moneyName.'数量不够！！']);
        }
        $user_product = new UserProduct();
        //扣除账户金币 该用户开采率增加 增加user_product记录
        Db::startTrans();
        try {
            $user_magic_log = new UserMagicLog();
            $user_jewel_log = new UserJewelLog();
            if ($type == 1) {
                //增加金币流水记录
                $res3 = $user_magic_log->addInfo($this->userId, "购买茶园", $product['price'] * (-1), $userInfo['magic'], $userInfo['magic'] - $product['price'], 2);
                $userInfo->magic = $userInfo->magic - $product->price;
            } elseif ($type == 2) {
                //增加金条流水记录
                $res3 = $user_jewel_log->addInfo($this->userId, "购买茶园", $product['jewel_price'] * (-1), $userInfo['jewel'], $userInfo['jewel'] - $product['jewel_price']);
                $userInfo->jewel = $userInfo->jewel - $product->jewel_price;
            }

            if (!$res3) {
                throw new \Exception('茶园流水记录增加失败');
            }

            $userInfo->product_rate = $userInfo->product_rate + $product->getRate();

            $res = $userInfo->save();

            if (!$res) {
                throw new \Exception('用户资料修改失败');
            }

            //增加user_product记录
            $res2 = $user_product->createInfo($product, $this->userId, 2);

            if ($userInfo['pid'] && $type == 1) {
                //得到后台配置的直推奖励
                $val = Config::getValue("rules_spread_rate");
                if ($val) {

                    //增加上级的金币收益
                    //烧伤制度：上级得到的金币收益 = 用户购买的魔盒等级 > 父级拥有最高级魔盒 ? 父级拥有最高魔盒价格*后台设置的直推奖励百分比 : 用户购买魔盒价格*后台设置的直推奖励百分比
                    //1得到父级的详细信息
                    $userInfo_p = $user->getInfo($userInfo['pid']);

                    //2.查看父级是否拥有比用户购买魔盒更高级的魔盒
                    $moreBox = UserProduct::where("product_id", ">=", $product_id)->where("user_id", $userInfo['pid'])->find();

                    $old_magic = $userInfo_p->magic;
                    //父级存在比用户更高等级的魔盒
                    if ($moreBox) {
                        $userInfo_p->magic = $userInfo_p->magic + ($val / 100) * $product['price'];
                        $price = $product['price'];
                        $userInfo_p->save();
                    } else {
                        //查询出父级最小等级的魔盒
                        $moreBox = UserProduct::where("product_id", "ELT", $product_id)->where("user_id", $userInfo['pid'])->order("product_id desc")->select();
                        $minbox = $moreBox[0] ? $moreBox[0] : 0;
                        if ($minbox) {
                            $product_min = $productModel->getInfoById($minbox->product_id);
                            //父级最小魔盒价格*后台设置的直推奖励百分比的金币
                            $userInfo_p->magic = $userInfo_p->magic + ($val / 100) * $product_min['price'];
                            $price = $product_min['price'];
                        }
                    }
                    if ($userInfo_p->save() === false) {
                        throw new \Exception('增加父级金币数量失败');
                    }
                    if (isset($price) && $price > 0) {
                        //增加父级金币流水记录
                        $resp = $user_magic_log->addInfo($userInfo_p->id, UserMagicLog::TYPE_REWARD, $price * ($val / 100), $old_magic, $userInfo_p->magic, 4);
                        if (!$resp) {
                            throw new \Exception('增加父级金币流水记录失败');
                        }
                    }
                }

            }


            if (!$res2) {
                throw new \Exception('增加user_product记录失败');
            }


            Db::commit();
            return json(['code' => 0, 'msg' => '购买成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 购买魔盒
     */
    public function recharge_old(Request $request) {
        $product_id = $request->post("product_id");
        //得到魔盒详细信息
        $productModel = new productModel();
        $product = $productModel->getInfoById($product_id);
        if (!$product) {
            return json(['code' => 1, 'msg' => '该茶园类型不存在']);
        }

        //获取用户详细信息
        $user = new \app\index\model\User();
        $userInfo = $user->getInfo($this->userId);

        //查看该用户等级最多拥有魔盒数量 高级 中级 初级 迷你
        $config = new Config();
        $max_box = $config->getMaxBox($userInfo["level"]);
        $should_max_box = 0;
        //这是为了将该有的魔盒数量以及用户拥有的魔盒数量与产品id进行对应 高级4 中级3 初级2 迷你1
        if ($product_id == 4) {
            $should_max_box = $max_box[0];
        }
        if ($product_id == 3) {
            $should_max_box = $max_box[1];
        }
        if ($product_id == 2) {
            $should_max_box = $max_box[2];
        }
        if ($product_id == 1) {
            $should_max_box = $max_box[3];
        }

        //查询出该用户所拥有的魔盒数量
        $user_product = new UserProduct();
        $boxList = $user_product->getBox($userInfo['id'], $product_id);

        //判断用户是否可以买这个规格的魔盒
        if (count($boxList) >= $should_max_box) {
            return json(['code' => 1, 'msg' => '您拥有的该类型茶园已经达到上限了']);
        }
        //得到用户购买魔盒方式 1金币 2金条
        $type = $request->post("type");

        //判断用户账户金币数量是否足够支付购买该魔盒
        if (($userInfo['magic'] < $product['price']) && $type == 1) {
            return json(['code' => 1, 'msg' => '您账户金币数量不够！！']);
        }

        //判断用户账户金条数量是否足够支付购买该魔盒
        if (($userInfo['jewel'] < $product['jewel_price']) && $type == 2) {
            return json(['code' => 1, 'msg' => '您账户金条数量不够！！']);
        }

        //扣除账户金币 该用户开采率增加 增加user_product记录
        Db::startTrans();
        try {
            $user_magic_log = new UserMagicLog();
            $user_jewel_log = new UserJewelLog();
            if ($type == 1) {
                //增加金币流水记录
                $res3 = $user_magic_log->addInfo($this->userId, "购买茶园", $product['price'] * (-1), $userInfo['magic'], $userInfo['magic'] - $product['price'], 2);
                $userInfo->magic = $userInfo->magic - $product->price;
            } elseif ($type == 2) {
                //增加金条流水记录
                $res3 = $user_jewel_log->addInfo($this->userId, "购买茶园", $product['jewel_price'] * (-1), $userInfo['jewel'], $userInfo['jewel'] - $product['jewel_price']);
                $userInfo->jewel = $userInfo->jewel - $product->jewel_price;
            }

            if (!$res3) {
                throw new \Exception('茶园流水记录增加失败');
            }

            $userInfo->product_rate = $userInfo->product_rate + $product->getRate();

            $res = $userInfo->save();

            if (!$res) {
                throw new \Exception('用户资料修改失败');
            }

            //增加user_product记录
            $res2 = $user_product->createInfo($product, $this->userId, 2);

            if ($userInfo['pid'] && $type == 1) {
                //得到后台配置的直推奖励
                $val = Config::getValue("rules_spread_rate");
                //增加上级的金币收益
                //烧伤制度：上级得到的金币收益 = 用户购买的魔盒等级 > 父级拥有最高级魔盒 ? 父级拥有最高魔盒价格*后台设置的直推奖励百分比 : 用户购买魔盒价格*后台设置的直推奖励百分比
                //1得到父级的详细信息
                $userInfo_p = $user->getInfo($userInfo['pid']);

                //2.查看父级是否拥有比用户购买魔盒更高级的魔盒
                $moreBox = UserProduct::where("product_id", ">=", $product_id)->where("user_id", $userInfo['pid'])->find();

                $old_magic = $userInfo_p->magic;
                //父级存在比用户更高等级的魔盒
                if ($moreBox) {
                    $userInfo_p->magic = $userInfo_p->magic + ($val / 100) * $product['price'];
                    $price = $product['price'];
                    $userInfo_p->save();
                } else {
                    //查询出父级最小等级的魔盒
                    $moreBox = UserProduct::where("product_id", "ELT", $product_id)->where("user_id", $userInfo['pid'])->order("product_id desc")->select();
                    $minbox = $moreBox[0] ?? 0;
                    if ($minbox) {
                        $product_min = $productModel->getInfoById($minbox->product_id);
                        //父级最小魔盒价格*后台设置的直推奖励百分比的金币
                        $userInfo_p->magic = $userInfo_p->magic + ($val / 100) * $product_min['price'];
                        $price = $product_min['price'];
                    }
                }
                if ($userInfo_p->save() === false) {
                    throw new \Exception('增加父级金币数量失败');
                }
                if (isset($price) && $price > 0) {
                    //增加父级金币流水记录
                    $resp = $user_magic_log->addInfo($userInfo_p->id, UserMagicLog::TYPE_REWARD, $price * ($val / 100), $old_magic, $userInfo_p->magic, 4);
                    if (!$resp) {
                        throw new \Exception('增加父级金币流水记录失败');
                    }
                }
            }


            if (!$res2) {
                throw new \Exception('增加user_product记录失败');
            }


            Db::commit();
            return json(['code' => 0, 'msg' => '购买成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

}
