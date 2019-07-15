<?php


if (!function_exists('exception_api')) {
    /**
     * @param int $code 异常代码
     * @param string $msg 异常信息
     * @throws \yiqiniu\exception\ApiException
     */
    function exception_api($code, $msg)
    {
        if (!is_numeric($code) && !empty($msg)) {
            $msg2 = $code;
            $code = $msg;
            $msg = $msg2;
        }
        if (!is_numeric($code)) {
            $msg = $code;
            $code = 400;
        }
        throw  new yiqiniu\exception\ApiException($msg, $code);
    }
}




/**
 * 检测浏览器类型，成功返回
 * @return string
 *
 */
function get_browser_type(){

    if(empty($_SERVER['HTTP_USER_AGENT'])){
        return $_SERVER['HTTP_USER_AGENT'];
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 9.0')){
        //return 'Internet Explorer 9.0';
        return 'internet explorer';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 8.0')){
        //return 'Internet Explorer 8.0';
        return 'internet explorer';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 7.0')){
        //return 'Internet Explorer 7.0';
        return 'internet explorer';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6.0')){
        //return 'Internet Explorer 6.0';
        return 'internet explorer';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'Edge')){
        //return 'Firefox';
        return 'edge';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'Firefox')){
        //return 'Firefox';
        return 'firefox';
    }

    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'Chrome')){
        //return 'Chrome';
        $str = substr($_SERVER['HTTP_USER_AGENT'],strpos($_SERVER['HTTP_USER_AGENT'],'Chrome')+7,2);
        return $str>'45'?'chrome':'chrome45';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'Safari')){
        // return 'Safari';
        return 'safari';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'Opera')){
        // return 'Opera';
        return 'opera';
    }
    if(false!==strpos($_SERVER['HTTP_USER_AGENT'],'360SE')){
        //   return '360SE';
        return '360se';
    }
}





// 注册命令行指令
\think\Console::addDefaultCommands([
    '\\yiqiniu\\console\\command\\MakeFacade',
    '\\yiqiniu\\console\\command\\ModelAll',
    '\\yiqiniu\\console\\command\\ValidateAll',
    '\\yiqiniu\\console\\command\\Compress',
]);
//添加swoole的支持
if (extension_loaded('swoole')) {
    \think\Console::addDefaultCommands([
        '\\yiqiniu\\console\\command\\Tcpserver'
    ]);
}
