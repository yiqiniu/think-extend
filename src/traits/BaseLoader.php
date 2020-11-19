<?php
namespace yiqiniu\traits;


use think\Exception;

/**
 * Class BaseLoader
 * @package app\facaiapi\loader
 */
abstract class BaseLoader
{

    //
    private static $_class_instance = [];
    // 当前对象
    private static $_instance = [];

    // 命名空间
    protected $_namespace = '';
    // 类前缀
    protected $_prefix = '';
    // 类后缀
    protected $_suffix = '';


    /**
     * BaseLoader constructor.
     * @param mixed $arguments
     */
    public function __construct($arguments = null)
    {
        //
        if (empty($this->_namespace)) {

            $class = get_class($this);
            $this->_namespace = dirname($class);
        }
    }


    /**
     * 动态调用函数的接口
     * @param $classname
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($classname, $arguments)
    {
        // 获取调用者，产生一个单列调用值
        $loader_key = md5(static::class);
        if (empty(self::$_instance[$loader_key])) {
            self::$_instance[$loader_key] = new static($arguments);
        }
        // 调用的不是全路径时，生成一个全路径的类名
        if (strpos($classname, '\\') === false) {
            $classname = self::$_instance[$loader_key]->_namespace . '\\' .
                ucfirst(self::$_instance[$loader_key]->_prefix) . ucfirst($classname) . ucfirst(self::$_instance[$loader_key]->_suffix);
        }
        // 生成类名的关键字，形成一个单列调用
        $key = md5($classname);
        if (!isset(self::$_class_instance[$key])) {
            if (!class_exists($classname)) {
                throw  new Exception($classname . ' 未找到');
            }
            self::$_class_instance[$key] = new $classname();
        }

        // 如果类中调用的_initilize 进行调用进行初始化
        if (method_exists(self::$_class_instance[$key], '_initilize')) {
            // 初始化操作
            self::$_class_instance[$key]->_initilize($arguments);
        }

        // 返回类对象
        return self::$_class_instance[$key];
    }


}