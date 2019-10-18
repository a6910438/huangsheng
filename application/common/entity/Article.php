<?php
namespace app\common\entity;

use think\Model;

class Article extends Model
{
    const STATUS_SHOW = 1;
    const STATUS_HIDDEN = 0;

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'article';

    protected $autoWriteTimestamp = false;

    public static function getAllCate()
    {
        return [
            '1' => '用户协议',
            '2' => 'GTC说明',
            '3'=>'客服中心',
            '4'=>'公告',
            '5'=>'关于我们',
        ];
    }

    public function isShow()
    {
        return $this->status == self::STATUS_SHOW ? true : false;
    }

    public function getCate()
    {
        $allCate = self::getAllCate();
        return $allCate[$this->category] ?? '';
    }

    public function getDetail($article_id){
        return self::where('article_id',$article_id)->find();
    }

    public function getList($category){
        return self::where('category',$category)->where('status',1)->select();
    }

    public static function deleteByArticleId($articleId)
    {
        return self::where('article_id', $articleId)->delete();
    }

    public function getCreateTime()
    {
        return $this->create_time;
    }

    public function addArticle($data)
    {
        $entity = new self();
        $entity->category = $data['category'];
        $entity->title = $data['title'];
        $entity->content = $data['content'];
        $entity->status = $data['status'];
        $entity->create_time = time();
        $entity->sort = $data['sort'] ?? 0;

        return $entity->save();
    }

    public function updateArticle(Article $article, $data)
    {
        $article->category = $data['category'];
        $article->title = $data['title'];
        $article->content = $data['content'];
        $article->status = $data['status'];
        $article->create_time = time();
        $article->sort = $data['sort'] ?? 0;


        return $article->save();
    }

    public function updImgUrl($str){
        $aa = preg_replace("/\/uploads/","../upload",$str);
        return $aa;
    }
}