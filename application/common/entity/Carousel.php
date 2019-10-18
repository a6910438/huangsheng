<?php

namespace app\common\entity;

use think\Model;

class Carousel extends Model {

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'carousel';
    protected $auto = ['create_time'];

    //返回原有数据  不自动进行时间转换
    public function getCreateTimeAttr($time) {
        return $time;
    }

    public static function allType() {
        return [
            1 => '首页轮播图',
            99 => '其他',
        ];
    }

    public function getType() {
        $allType = self::allType();
        return $allType[$this->type_id] ? $allType[$this->type_id] : '';
    }

    public function addCarousel($data) {
        $entity = new self();
        $entity->title = $data['title'];
        $entity->path = $data['path'];
        $entity->url = $data['url'];
        $entity->type_id = $data['type_id'];
        $entity->order_number = $data['order_number'];
        $entity->status = 1;
        $entity->create_time = date('Y-m-d H:i:s', time());

        return $entity->save();
    }

    public function updateCarousel(Carousel $entity, $data) {
        $entity->title = $data['title'];
        $entity->path = $data['path'];
        $entity->url = $data['url'];
        $entity->type_id = $data['type_id'];
        $entity->order_number = $data['order_number'];

        return $entity->save();
    }

    public function getListByTid($typeid) {
        $list = $this->where('type_id', $typeid)->order('order_number DESC')->select();
        if (count($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['url'] = $v['url'] ? $v['url'] : 'javascript:;';
            }
        }
        return $list;
    }

}
