<?php


namespace yiqiniu\db;


use think\Db;
use think\Loader;

/**
 * Class BaseModel
 * @package yiqiniu\db
 */
class BaseModel
{


    /**
     * 默认缓存时间
     */
    const DEFAULT_CACHE_TIME = 300;

    /**
     * 默认page_size 为30
     */
    const DEFAULT_PAGE_SIZE = 30;

    /**
     * 默认表名
     * @var string
     */
    protected $name = '';
    /**
     * 默认主键
     * @var string
     */
    protected $pk = 'id';


    public function __construct()
    {
        $this->name = Loader::parseName($this->name, 1);
    }

    /**
     * 获取当前的处理类
     * @param string $name
     * @return \think\db\Query
     */
    protected function db($name = '')
    {
        return Db::name(empty($name) ? $this->name : $name);
    }
    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}