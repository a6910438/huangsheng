<?php
namespace app\common\entity;

class Export 
{
    /**
     * 创建(导出)Excel数据表格
     * @param  array $list 要导出的数组格式的数据
     * @param  string $filename 导出的Excel表格数据表的文件名
     * @param  array $header Excel表格的表头
     * @param  array $index $list数组中与Excel表格表头$header中每个项目对应的字段的名字(key值)
     * 比如: $header = array('编号','姓名','性别','年龄');
     *       $index = array('id','username','sex','age');
     *       $list = array(array('id'=>1,'username'=>'YQJ','sex'=>'男','age'=>24));
     * @return [array] [数组]
     */
    public static function createtable($list, $filename, $header = array(), $index = array())
    {
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . $filename . ".xls");
        $teble_header = implode("\t", $header);
        $strexport = $teble_header . "\r";
        foreach ($list as $row) {
            foreach ($index as $val) {
                $strexport .= $row[$val] . "\t";
            }
            $strexport .= "\r";

        }
        $strexport = iconv('UTF-8', "GB2312//IGNORE", $strexport);
        exit($strexport);
    }
}