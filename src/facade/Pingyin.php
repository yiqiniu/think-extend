<?php


namespace yiqiniu\extend\facade;


use think\Facade;


/**
 * Class Pingyin
 * @package yiqiniu\facade
 * @method \yiqiniu\extend\library\Pingyin str2py(string $s, bool $first = true, string $separate = '') 把汉字转换成拼音
 */
class Pingyin extends Facade
{
    protected static function getFacadeClass()
    {
        return 'yiqiniu\extend\library\Pingyin';
    }


}