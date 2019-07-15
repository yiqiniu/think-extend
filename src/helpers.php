<?php


use yiqiniu\exception\ApiException;

if (!function_exists('exception_api')) {
    /**
     * @param int $code         异常代码
     * @param string $msg       异常信息
     * @throws \yiqiniu\exception\ApiException
     */
    function exception_api($code, $msg)
    {
        throw  new ApiException($msg, $code);
    }
}