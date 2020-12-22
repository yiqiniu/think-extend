<?php


namespace yiqiniu\extend\facade;


use yiqiniu\extend\library\{Arrays, Date, Http, Logger, Pingyin, Redis, Str, Token};
use yiqiniu\extend\traits\BaseLoader;

/**
 * 扩展函数库
 * Class Extend
 * @package yiqiniu\facade
 * @method Arrays       Arrays() static     数组相关
 * @method Date         Date() static       日期相关
 * @method Http         Http() static       Curl 请求相关
 * @method Logger       Logger() static     日志相关
 * @method Pingyin      Pingyin() static    汉字转拼音
 * @method Redis        Redis() static      Redis函数
 * @method Str          String() static     字符串相关
 * @method Token        Token() static      JWT相关
 */
class Extend extends BaseLoader
{
    // 命名空间
    protected $_namespace = 'yiqiniu\extend\library';
    // 类前缀
    protected $_prefix = '';
    // 类后缀
    protected $_suffix = '';
}











