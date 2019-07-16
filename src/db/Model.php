<?php


namespace yiqiniu\db;


use think\Exception;
use think\facade\Cache;
use yiqiniu\facade\Redis;

/**
 * Class Model
 * @package yiqiniu\db
 * @method Query findArray(mixed $data = null) static 查询单个记录
 * @method Query selectArray(mixed $data = null) static 查询多个记录
 */
class Model extends \think\Model
{



    public function return_page($page_list, $list = null)
    {
        $list = is_null($list) ? $page_list->items() : $list;
        if ($this->page_simple) {
            $r_arr = ['hasmore' => $page_list->hasPage, 'list' => $list];
        } else {
            $r_arr = ['total' => $page_list->total, 'current_page' => $page_list->currentPage,
                'last_page' => $page_list->lastPage, 'list_rows' => $page_list->listRows, 'render' => $page_list->render(), 'list' => $list];
        }
        return $r_arr;
    }

    /**
     * 添加修改
     * @param $data
     * @return int|string
     * @throws Exception
     */
    public function addEdit($data)
    {
        try {
            $id = empty($data[$this->pk]) ? '' : $data[$this->pk];
            if (empty($id))
                $this->where([$this->pk => $id])->update($data);
            else
                $id = $this->insert($data);
            return $id;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 删除数据
     * @param $id
     * @throws Exception
     */
    public function del($id)
    {
        try {
            $this->where([$this->pk => $id])->delete();
        } catch (Exception $e) {
            throw $e;
        }
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
            $allTag = Redis::get('tag_all');
            if (!$list || !$allTag || !in_array($this->name, $allTag)) {
                $list = array_column($this->select()->toArray(), null, $this->pk);
                $this->redisHset('list', $list);
                $allTag[] = $this->name;
                Redis::set('tag_all', $allTag);
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
            if (empty($ids))
                return [];
            $ids = is_array($ids) ? $ids : [$ids];
            $list = $this->redisHget('list', $ids);
            $ids_r = array_column($list, $this->pk);
            $ids_w = array_diff($ids, $ids_r);
            if (!empty($ids_w)) {
                $diff_list = array_column($this->where([$this->pk => $ids_w])->select()->toArray(), null, $this->pk);
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
            if (empty($id))
                return false;
            $info = $this->redisHget('list', $id);
            if (empty($info)) {
                $info = $this->where($this->pk, $id)->find();
                if ($info)
                    $this->redisHset('list', [$info[$this->pk] => $info->toArray()]);
            }
            return is_object($info) ? $info->toArray() : $info;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 修改字段并且同步缓存
     * @param $list
     * @param $fields
     * @throws Exception
     */
    public function redisSave($list, $fields)
    {
        try {
            $this->startTrans();
            $list = is_object($list) ? $list->toArray() : $list;
            $fields_a = is_array($fields) ? $fields : explode(',', $fields);
            $ids = [];
            foreach ($list as $key => $row) {
                $up = [];
                foreach ($fields_a as $fd) {
                    $up[trim($fd)] = $row[trim($fd)];
                }
                $this->where([$this->pk => $key])->update($up);
                $ids[] = $key;
            }
            $tlist = $this->where([$this->pk => $ids])->column('*', $this->pk);
            $this->redisHset('list', $tlist);
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 删除数据并且同步缓存
     * @param $id
     * @throws Exception
     */
    public function redisDel($id)
    {
        $this->redisHdel('list', $id);
    }


    /**
     * 列表缓存设置
     * @param $name
     * @param $ids
     * @return mixed
     */
    public function redisHset($name, $list)
    {
        $name = $this->name . '_' . $name;
        return Redis::hset($name, $list);
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
        return Redis::hget($name, $ids);
    }

    /**
     * 列表缓存删除
     * @param $name
     * @param $ids
     * @return mixed
     */
    public function redisHdel($name, $ids)
    {
        $name = $this->name . '_' . $name;
        return Redis::hdel($name, $ids);
    }

    /**
     * 设置缓存
     * @param $key 键名
     * @param $value 键值
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
        if (empty($key))
            Cache::clear($this_name);
        else
            Cache::rm($this_name . '_' . $key);
    }
}