<?php
/**
 * Created by PhpStorm.
 * User: gjianbo
 * Date: 2019/1/12
 * Time: 17:26
 */

namespace yiqiniu\extend\traits;

/**
 * 单例模式
 * Trait Single
 * @package tpext\traits
 */
trait Singleton
{
    protected static $_instance = [];

    /**
     * Procdata constructor.
     * @param mixed ...$args
     */
    private function __construct(...$args)
    {
        if (method_exists($this, '_initConfig')) {
            $this->_initConfig(...$args);
        }
        if (method_exists($this, '_init')) {
            $this->_init();
        }
    }


    public static function getInstance(...$args)
    {
        // 获取调用者，产生一个单列调用值
        $loader_key = md5(static::class);
        if (empty(self::$_instance[$loader_key])) {
            self::$_instance[$loader_key] = new static($args);
        }
        if (method_exists(self::$_instance[$loader_key], '_initilize')) {
            self::$_instance[$loader_key]->_initilize();
        }
        return self::$_instance[$loader_key];
    }




}