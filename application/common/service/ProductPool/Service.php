<?php
namespace app\common\service\ProductPool;

use app\common\entity\ProductPool;
use think\Request;
use think\Session;

class Service
{

    public function addData($data)
    {

        $entity = new ProductPool();
        $entity->name = trim($data['name']);
//        $entity->lv = $data['lv'];
        $entity->lock_position = empty($data['lock_position']) ? '0' : $data['lock_position'];
        $entity->status = $data['status'];
        $entity->worth_min = $data['worth_min'];
        $entity->worth_max = $data['worth_max'];
        $entity->start_time = strtotime($data['start_time']);
        $entity->end_time = strtotime($data['end_time']);
        $entity->about_start_time = strtotime($data['about_start_time']);
        $entity->about_end_time = strtotime($data['about_end_time']);
        $entity->bait = $data['bait'];
        $entity->subscribe_bait = $data['subscribe_bait'];
        $entity->rob_bait = $data['rob_bait'];
        $entity->fail_return = $data['fail_return'];
        $entity->profit = $data['profit'];
        $entity->contract_time = $data['contract_time'];
        $entity->is_open = $data['is_open'];
        $entity->remarks = trim($data['remarks']);
        $entity->num = $data['num']?trim($data['num']):0;
        $entity->sort = $data['sort'];
        $entity->img = $data['path'];
        $entity->first_section_min = $data['first_section_min'];
        $entity->first_section_max = $data['first_section_max'];
        $entity->first_section_percent = $data['first_section_percent'];
        $entity->second_section_min = $data['second_section_min'];
        $entity->second_section_max = $data['second_section_max'];
        $entity->second_section_percent = $data['second_section_percent'];
        $entity->third_section_min = $data['third_section_min'];
        $entity->third_section_max = $data['third_section_max'];
        $entity->third_section_percent = $data['third_section_percent'];
        $entity->open_section = $data['open_section'];
        $entity->create_time = time();

        if ($entity->save()) {
            return true;
        }

        return false;
    }



    /**
     * 检查等级是否已存在
     */
    public function checkLv($val, $id = 0)
    {

        $entity = ProductPool::where('lv', $val);
        if ($id) {
            $entity->where('id', '<>', $id);
            $entity->where('is_delete', '<>', 0);
        }
        return $entity->find() ? true : false;
    }


    /**
     * 时间验证
     * @param $about_start_time  预约开始时间
     * @param $about_end_time    预约结束时间
     * @param $start_time        领取开始时间
     * @param $end_time          领取结束时间
     * @return mixed
     */
    public function checkTime($about_start_time, $about_end_time,$start_time,$end_time   )
    {

        $arr['code'] = 0;


        if($about_start_time >= $about_end_time || $start_time >= $end_time){
            $arr['code'] = 1;
            $arr['message'] = '结束时间必须大于开始时间！';
        }
        if($about_end_time > $start_time ){
            $arr['code'] = 1;
            $arr['message'] = '预约时间必须大于领取时间！';
        }
        return $arr;

    }

    /**
     * 分配比例判断
     * @param $first_section_percente
     * @param $second_section_percent
     * @param $third_section_percent
     * @return mixed
     */
    public function checkSection($first_section_percente, $second_section_percent,$third_section_percent  )
    {

        $arr['code'] = 0;

        $num  = (float)$first_section_percente + (float)$second_section_percent + (float)$third_section_percent;

        if($num < 0 || $num > 100){
            $arr['code'] = 1;
            $arr['message'] = '分配比例必须在0-100之间！';
        }

        return $arr;

    }



}