<?php


namespace yiqiniu\extend\facade;


use think\Facade;

/**
 * Class Logger
 * @package yiqiniu\facade
 * @method \yiqiniu\extend\library\Logger exception(mixed $exception) static  保存异常信息到文件
 * @method \yiqiniu\extend\library\Logger writeLogger(string $filename,mixed $strdata, bool  $append=true) static  写内容到文件中
 * @method \yiqiniu\extend\library\Logger log(mixed $strdata, bool  $append=true,string $perfix='',string $dir='logs',string $format='array') static  写内容到默认文件中
 */
class Logger extends Facade
{
    protected static function getFacadeClass()
    {
        return 'yiqiniu\extend\library\Logger';
    }


}