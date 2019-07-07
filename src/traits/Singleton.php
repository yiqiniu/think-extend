<?php
/**
 * Created by PhpStorm.
 * User: gjianbo
 * Date: 2019/1/12
 * Time: 17:26
 */

namespace yiqiniu\traits;

/**
 * 单例模式
 * Trait Single
 * @package tpext\traits
 */
trait Singleton
{
    protected static $_instance = null;

    /**
     * Procdata constructor.
     * @param null $_instance
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
        if (self::$_instance == null) {
            self::$_instance = new self(...$args);
        }
        return self::$_instance;
    }


}