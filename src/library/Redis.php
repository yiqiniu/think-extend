<?php
/**
 * Created by PhpStorm.
 * User: songpeipeng
 * Date: 2018/10/8
 * Time: 下午2:44
 */

namespace yiqiniu\library;


class Redis
{
    /**
     * 序列化方法
     * @var array
     */
    protected static $serialize = ['serialize', 'unserialize', 'think_serialize:', 16];
    protected $select = 0;
    /**
     * 缓存参数
     * @var array
     */
    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
        'serialize' => true,
    ];
    /**
     * 驱动句柄
     * @var object
     */
    protected $handler = null;
    /**
     * 缓存标签
     * @var string
     */
    protected $tag;
    /**
     * 缓存读取次数
     * @var integer
     */
    protected $readTimes = 0;

    /**
     * 缓存写入次数
     * @var integer
     */
    protected $writeTimes = 0;

    /**
     * 架构函数
     * @access public
     * @param  array $options 缓存参数
     */
    public function __construct($options = [])
    {
        $this->init($options);
    }

    /**
     * 自动初始化缓存
     * @access public
     * @param  array $options 配置数组
     * @param  bool $force 强制更新
     * @return Driver
     */
    public function init(array $options = [], $force = false)
    {
        if (is_null($this->handler) || $force) {

            if ($r = config("redis."))
                $this->options = array_merge($this->options, $r);
            if (!empty($options)) {
                $this->options = array_merge($this->options, $options);
            }
            $this->handler = new \Redis;
            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }

            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
                $this->select = $this->options['select'];
            }
        }
        return $this;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getKey($name);
        $re = $this->handler->incrby($key, $step);
        return $re;
    }

    /**
     * 获取实际的缓存标识
     * @access protected
     * @param  string $name 缓存名
     * @return string
     */
    protected function getKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getKey($name);
        $re = $this->handler->decrby($key, $step);
        return $re;
    }

    /**
     * 清除缓存
     * @access public
     * @param  string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);

            foreach ($keys as $key) {
                $this->handler->delete($key);
            }

            $this->rm('tag_' . md5($tag));
            return true;
        }

        $this->writeTimes++;
        $re = $this->handler->flushDB();
        return $re;
    }

    /**
     * 获取标签包含的缓存标识
     * @access protected
     * @param  string $tag 缓存标签
     * @return array
     */
    protected function getTagItem($tag)
    {
        $key = 'tag_' . $tag;
        $value = $this->get($key);

        if ($value) {
            return array_filter(explode(',', $value));
        } else {
            return [];
        }
    }

    /**
     * 读取缓存
     * @access public
     * @param  string $name 缓存变量名
     * @param  mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $value = $this->handler->get($this->getKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }
        return $this->unserialize($value);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param  string $data
     * @return mixed
     */
    protected function unserialize($data)
    {
        if ($this->options['serialize'] && 0 === strpos($data, self::$serialize[2])) {
            $unserialize = self::$serialize[1];

            return $unserialize(substr($data, self::$serialize[3]));
        } else {
            return $data;
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param  string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        $this->writeTimes++;
        $re = $this->handler->delete($this->getKey($name));
        return $re;
    }

    /**
     * 设置哈希表存储列表
     * @param $name
     * @param $list
     * @return bool
     */
    public function hset($name, $list)
    {
        $this->writeTimes++;
        $key = $this->getKey($name);
        $tlist = [];
        foreach ($list as $field => $value) {
            $tlist[$field] = $this->serialize($value);
        }
        $result = $this->handler->hMSet($key, $tlist);
        return $result;
    }

    /**
     * 序列化数据
     * @access protected
     * @param  mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        if (is_scalar($data) || !$this->options['serialize']) {
            return $data;
        }

        $serialize = self::$serialize[0];

        return self::$serialize[2] . $serialize($data);
    }

    /**
     * 获取哈希表获取列表
     * @param $name
     * @param null $keys
     * @return array
     */
    public function hget($name, $keys = null)
    {
        $this->readTimes++;
        $name = $this->getKey($name);
        if (is_null($keys))
            $list = $this->handler->hGetAll($name);
        else if (is_array($keys)) {
            $list = $this->handler->hMGet($name, $keys);
        } else {
            $value = $this->unserialize($this->handler->hGet($name, $keys));
            return $value;
        }

        $relist = [];
        if ($list) {
            foreach ($list as $key => $value) {
                $relist[$key] = $this->unserialize($value);
            }
        }
        return $relist;
    }

    /**
     * 哈希表删除
     * @param $name
     * @param null $keys
     * @return mixed
     */
    public function hdel($name, $keys = null)
    {
        $name = $this->getKey($name);
        if (is_null($keys)) {
            $re = $this->handler->del($name);
        } else {
            $keys = is_array($keys) ? $keys : [$keys];
            foreach ($keys as $key) {
                $re = $this->handler->hDel($name, $key);
            }
        }
        return $re;
    }

    /**
     * 更新库
     * @param $index
     * @return bool
     */
    public function select($index)
    {
        $this->handler->select($index);
        $this->select = $index;
        return $this;
    }

    /**
     * 缓存标签
     * @access public
     * @param  string $name 标签名
     * @param  string|array $keys 缓存标识
     * @param  bool $overlay 是否覆盖
     * @return $this
     */

    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            $key = 'tag_' . $name;

            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            $keys = array_map([$this, 'getKey'], $keys);

            if ($overlay) {
                $value = $keys;
            } else {
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }

            $this->set($key, implode(',', $value), 0);
        }
        return $this;
    }

    /**
     * 写入缓存
     * @access public
     * @param  string $name 缓存变量名
     * @param  mixed $value 存储数据
     * @param  integer|\DateTime $expire 有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key = $this->getKey($name);
        $expire = $this->getExpireTime($expire);

        $value = $this->serialize($value);

        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }

        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 判断缓存
     * @access public
     * @param  string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        $re = $this->handler->exists($this->getKey($name));
        return $re;
    }

    /**
     * 获取有效期
     * @access protected
     * @param  integer|\DateTime $expire 有效期
     * @return integer
     */
    protected function getExpireTime($expire)
    {
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }

        return $expire;
    }

    /**
     * 更新标签
     * @access protected
     * @param  string $name 缓存标识
     * @return void
     */
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $key = 'tag_' . $this->tag;
            $prev = $this->tag;
            $this->tag = null;

            if ($this->has($key)) {
                $value = explode(',', $this->get($key));
                $value[] = $name;
                $value = implode(',', array_unique($value));
            } else {
                $value = $name;
            }

            $this->set($key, $value, 0);
            $this->tag = $prev;
        }
    }

    public function getReadTimes()
    {
        return $this->readTimes;
    }

    public function getWriteTimes()
    {
        return $this->writeTimes;
    }
}