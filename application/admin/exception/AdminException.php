<?php

namespace app\admin\exception;

use Throwable;

class AdminException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $code = $code ?: 400;
        parent::__construct($message, $code, $previous);
    }
}