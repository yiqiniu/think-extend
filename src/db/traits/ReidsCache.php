<?php


namespace yiqiniu\extend\db\traits;


use think\Cache;
use think\Exception;
use yiqiniu\extend\library\Redis;

/**
 * 缓存处理类
 * Trait ReidsCache
 * @package app\facaiapi\traits
 */
trait ReidsCache
{

    public $select_field = '*';
    protected $hredis = null;


    /**
     * 获取Reids对象
     */

    protected function getRedisObj()
    {
        if (empty($this->hredis)) {
            $this->hredis = new Redis();
        }
        return $this->hredis;
    }


    /**
     * 列表缓存设置
     * @param $name
     * @param $list
     * @return mixed
     */
    public function redisHset($name, $list)
    {
        $name = $this->name . '_' . $name;
        return $this->getRedisObj()->hset($name, $list);
    }

    /**
     * 列表缓存获取
     * @param $name
     * @param $ids
     * @return mixed
     */
    public function redisHget($name, $ids = null)
    {
        $name = $this->name . '_' . $name;
        return $this->getRedisObj()->hget($name, $ids);
    }

    /**
     * 列表缓存删除
     * @param $name
     * @param $ids
     * @return mixed
     */
    public function redisHdel($name, $ids = null)
    {
        $name = $this->name . '_' . $name;
        return $this->getRedisObj()->hdel($name, $ids);
    }


    /**
     * 删除数据并且同步缓存
     * @param $id
     */
    public function redisDel($id)
    {
        if (empty($id)) {
            return;
        }
        $op = is_array($id) ? 'in' : '=';
        $this->delete([$this->pk => [$op, $id]]);
        $this->redisHdel('list', $id);
    }


    /**
     * 从缓存获取全表
     * @return array
     * @throws Exception
     */
    public function redisAll()
    {
        try {
            $list = $this->redisHget('list');
            if (!$list) {
                $list = array_column($this->select(), null, $this->pk);
                $this->redisHset('list', $list);
            }
            return $list;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 从缓存获取列表
     * @param $ids
     * @return array
     * @throws Exception
     */
    public function redisList($ids)
    {
        try {
            if (empty($ids)) {
                return [];
            }
            $ids = is_array($ids) ? $ids : [$ids];
            $list = $this->redisHget('list', $ids);
            $ids_r = array_column($list, $this->pk);
            $ids_w = array_diff($ids, $ids_r);
            if (!empty($ids_w)) {
                $diff_list = array_column($this->where($this->pk,"in", $ids_w)->select(), null, $this->pk);
                $this->redisHset('list', $diff_list);
                foreach ($diff_list as $key => $row) {
                    $list[$key] = $row;
                }
            }
            return $list;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 从获取缓存详情
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function redisInfo($id)
    {
        try {
            if (empty($id)) {
                return false;
            }
            $info = $this->redisHget('list', $id);
            if (empty($info)) {
                $info = $this->where($this->pk, $id)->find();
                if ($info) {
                    $this->redisHset('list', [$info[$this->pk] => $info]);
                }
            }
            return $info;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 修改字段并且删除对应的缓存
     * @param $list
     * @param $fields
     * @throws Exception
     */
    public function redisSave($list, $fields)
    {
        try {
            $fields_a = is_array($fields) ? $fields : explode(',', $fields);
            foreach ($list as $key => $row) {
                $up = [];
                foreach ($fields_a as $fd) {
                    $fd = trim($fd);
                    $up[$fd] = $row[$fd];
                }
                $this->update([$this->pk => $key], $up);
                $this->redisHdel('list', $key);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 设置缓存
     * @param      $key 键名
     * @param      $value 键值
     * @param null $expire 过期时间
     * @return mixed
     */
    public function cacheSet($key, $value, $expire = null)
    {
        $this_name = $this->name;
        $name = $this_name . '_' . $key;
        return Cache::tag($this_name)->set($name, $value, $expire);
    }

    /**
     * 获取缓存
     * @param $key 键名
     * @return bool|mixed
     */
    public function cacheGet($key)
    {
        $this_name = $this->name;
        $key = $this_name . '_' . $key;
        return 0 === strpos($key, '?') ? Cache::has(substr($key, 1)) : Cache::get($key);
    }

    /**
     * 删除缓存
     * @param string $key 键名(null则删除TAG)
     */
    public function cacheDel($key = '')
    {
        $this_name = $this->name;
        if (empty($key)) {
            Cache::clear($this_name);
        } else {
            Cache::rm($this_name . '_' . $key);
        }
    }

    /**
     * 清空该表缓存
     */
    public function cacheClear()
    {
        $this->redisHdel('list');
        $this->cacheDel();
    }
}
