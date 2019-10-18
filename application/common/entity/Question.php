<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Question extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'question';
    protected $auto = ['create_time'];

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '启用';
            case 2:
                return '禁用';
            default:
                return '';
        }
    }
    public function arr($k)
    {
        $arr = [
            1=>'a',
            2=>'b',
            3=>'c',
            4=>'d',
            5=>'e',
            6=>'f',
            7=>'g',
            8=>'h',
            9=>'i',
            10=>'j',
            11=>'k',
            12=>'l',
            13=>'m',
            14=>'n',
            15=>'o',
            16=>'p',
            17=>'q',
            18=>'r',
            19=>'s',
            20=>'t',
            21=>'u',
            22=>'v',
            23=>'w',
            24=>'x',
            25=>'y',
            26=>'z',
        ];
        return $arr[$k];
    }
    //获取答案
    public function getAnswer($id)
    {
        $str = '';
        $data = Answer::where('qid',$id)
            ->where('status',1)
            ->order('sort')
            ->select();
        $table = 'class=table table-bordered';
        $start = '<table '.$table.'>
        <tr>
        <td>选项</td>
        <td>答案</td>
        <td>分数</td>
        </tr>
</tr>';
        foreach ($data as $k => $v){
            $str .= '<tr><td>'.$this->arr($k+1).'</td><td>'.$v['content'].'</td><td>'.$v['score'].'</td></tr> '.'<br>';
        }
        $end = '</table>';

        $info = $start.$str.$end;
        return $info;
    }
    //添加新数据
    public function addNew($query,$data)
    {

        $query->title = $data['title'];
        $query->sort = $data['sort'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }
    //删除数据
    public function delData($id)
    {

        Db::startTrans();
        try {
            $query = new Question();
            $res = $query->where('id',$id)->delete();
            if (!$res) {
                throw new \Exception('删除失败');
            }
            $entry = new Answer();
            if (!$entry->where('qid',$id)->delete()) {
                throw new \Exception('删除失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }



}
