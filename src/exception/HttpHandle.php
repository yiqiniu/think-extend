<?php

namespace yiqiniu\exception;

use yiqiniu\facade\Logger;

use Exception;
use think\exception\Handle;


class HttpHandle extends Handle
{

    /**
     * @param Exception $e
     * @return \think\Response|\think\response\Json
     */
    public function render(Exception $e)
    {
        Logger::exception($e);
        parent::render($e);
    }

}