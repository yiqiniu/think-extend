<?php
/**
 * Created by PhpStorm.
 * User: songpeipeng
 * Date: 2018/10/8
 * Time: 上午10:59
 */

namespace facade;

use think\Facade;

/**
 * @see \com\Redis
 * @mixin \com\Redis
 * @method mixed init(array $options = [], $force = false) static 自动初始化缓存
 * @method bool has($name) static 判断缓存是否存在
 * @method mixed get($name, $default = false) static 获取缓存
 * @method mixed set($name, $value, $expire = null) static 设置缓存
 * @method mixed inc($name, $step = 1) static 自增缓存（针对数值缓存）
 * @method mixed dec($name, $step = 1) static 自减缓存（针对数值缓存）
 * @method mixed rm($name) static 删除缓存
 * @method mixed clear($tag = null) static 清除缓存
 * @method mixed hset($name, $list) static 设置哈希表存储列表
 * @method mixed hget($name, $keys = null) static 获取哈希表获取列表
 * @method mixed hdel($name, $keys = null) static 哈希表删除
 * @method mixed select($index) static 更换缓存库
 * @method mixed tag($name, $keys = null, $overlay = false) static 缓存标签
 */
class Redis extends Facade
{

    protected static function getFacadeClass()
    {
        return 'yiqiniu\library\Redis';
    }
}