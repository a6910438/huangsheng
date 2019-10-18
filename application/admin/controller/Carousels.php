<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\Export;
use think\Db;
use think\Request;
use app\common\entity\Carousel;
use app\common\helper\func;

class Carousels extends Admin {

    /**
     * @power 内容管理|图片管理
     * 图片
     */
    public function index(Request $request) {
        $carouselModel = new Carousel();

        $entity = $carouselModel::field('*');
        if ($type = $request->get('type')) {
            $entity->where('type_id', $type);
            $map['type_id'] = $type;
        }

        $list = $entity->paginate(15, false, [
            'query' => isset($map) ? $map : []
        ]);

        return $this->render('carouselList', [
                    'list' => $list,
                    'type' => $carouselModel->allType(),
        ]);
    }

    /**
     * @power 内容管理|图片管理@添加图片
     */
    public function edit() {
        $id = request()->get('id', 0);
        $carouselModel = new Carousel();
        if ($id) {
            $entity = $carouselModel->where('id', $id)->find()->toArray();
            if (!$entity) {
                $this->error('用户对象不存在');
            }
            $title = '编辑图片';
        } else {
            $entity = [
                'id' => 0,
                'title' => '',
                'url' => '',
                'type_id' => 0,
                'path' => '',
                'order_number' => 0,
            ];
            $title = '添加图片';
        }
        $typeArr = $carouselModel->allType();

        return $this->render('edit', [
                    'title' => $title,
                    'typeArr' => $typeArr,
                    'info' => $entity,
                    'type' => $typeArr
        ]);
    }

    /**
     * @power 内容管理|图片管理@删除图片
     */
    public function delete(Request $request, $id) {
        $entity = Carousel::where('id', $id)->find();

        if (!$entity) {
            throw new AdminException('对象不存在');
        }
        if (!$entity->delete()) {
            throw new AdminException('删除失败');
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    /**
     * @power 内容管理|图片管理@添加图片
     */
    public function save(Request $request, $id) {
        $res = $this->validate($request->post(), 'app\admin\validate\Carousel');

        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }

        $carouselModel = new Carousel();
        if ($id) {
            $entity = $carouselModel->where('id', $id)->find();
            $result = $carouselModel->updateCarousel($entity, $request->post());
        } else {
            $result = $carouselModel->addCarousel($request->post());
        }
        if (!$result) {
            throw new AdminException('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('/admin/carousels')]);
    }

}
