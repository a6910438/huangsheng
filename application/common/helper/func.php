<?php

namespace app\common\helper;

class func {

    public static function fput($msg, $arr = 0, $ext = '') {
        $time = date('Y-m-d H:i:s', time());
        if ($arr) {
            file_put_contents(ROOT_PATH . '/log.txt', var_export($msg, true) . ' - ' . $ext . ' - ' . $time . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents(ROOT_PATH . '/log.txt', $msg . ' - ' . $ext . ' - ' . $time . PHP_EOL, FILE_APPEND);
        }
    }

    public static function printarr($arr, $exit = 0) {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
        if ($exit) {
            exit();
        }
    }

}
