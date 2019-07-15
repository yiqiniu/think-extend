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
    ];
}
