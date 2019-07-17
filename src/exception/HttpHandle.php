<?php

namespace yiqiniu\exception;

use yiqiniu\facade\Logger;

use Exception;
use think\exception\Handle;


class HttpHandle extends Handle
{


    public function report(Exception $exception){
        Logger::exception($exception);
        return parent::render($exception);
    }

   

}