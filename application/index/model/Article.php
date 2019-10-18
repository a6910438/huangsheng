<?php
namespace app\index\model;

use think\Db;
use think\facade\Request;

class Article
{
    /**
     * 得到文章的详细信息
     */
    public function getArticleList($category = '')
    {
        return \app\common\entity\Article::where('category', $category)->order('create_time DESC')->select();
    }

    /**
     * 文章详情
     */
    public function articleinfo($articleId)
    {
    	return \app\common\entity\Article::where('article_id', $articleId)->find();
    }
}