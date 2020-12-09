<?php

namespace yiqiniu\extend\traits;

trait MergeParams
{


    /**
     * 移除上次的设置
     * @return $this
     */
    public function removeOption(): self
    {
        $this->options = [];
        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param mixed $length 查询数量
     * @return $this
     */
    public function limit($length = null): self
    {
        $this->options['limit'] = $length;
        return $this;
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc'])
     * @access public
     * @param string $order 排序
     * @return $this
     */
    public function order($order): self
    {
        if (!empty($order)) {
            $this->options['order'] = $order;
        }
        return $this;

    }

    /**
     * 指定查询字段 支持字段排除和指定数据表
     * @access public
     * @param mixed $field
     * @return $this
     */
    public function field($field): self
    {
        if (!empty($field)) {
            $this->options['field'] = $field;
        }
        return $this;
    }

    /**
     * 返指定字段进行having操作
     * @param $field
     * @return $this
     */
    public function having($field): self
    {
        if (!empty($field)) {
            $this->options['having'] = $field;
        }
        return $this;
    }

    /**
     * 返指定字段进行分组操作
     * @param $field
     * @return $this
     */
    public function group($field): self
    {
        if (!empty($field)) {
            $this->options['group'] = $field;
        }
        return $this;
    }

    /**
     * 指定要排除的查询字段
     * @access public
     * @param array|string $field 要排除的字段
     * @return $this
     */
    public function withoutField($field)
    {
        if (!empty($field)) {
            $this->options['withoutField'] = $field;
        }
        return $this;
    }

    /**
     * 每页大小
     * @param int $pageSize
     * @return $this
     */
    public function pageSize(int $pageSize): self
    {
        $this->options['pagesize'] = $pageSize;
        return $this;
    }

    /**
     * 指定AND查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function where($field, $op = null, $condition = null): self
    {

        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        $where = &$this->options['where'];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if (is_array($value)) {
                    $value = count($value) === 1 ? ['=', current($value)] : $value;
                } else {
                    $value = ['=', $value];
                }
                $where[$key] = $value;
            }
        } else {
            if ($condition === null) {
                $condition = $op;
                $op = '=';
            }
            $where[$field] = [$op, $condition];
        }
        return $this;
    }

    /**
     * in 条件
     * @param $field
     * @param $condition
     * @return $this
     */
    public function whereIn($field, $condition): self
    {

        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        $where = &$this->options['where'];
        $where[$field] = ['in', $condition];
        return $this;
    }

    /**
     * notin 条件
     * @param $field
     * @param $condition
     * @return $this
     */
    public function whereNotIn($field, $condition)
    {

        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        $where = &$this->options['where'];
        $where[$field] = ['not in', $condition];
        return $this;
    }

    /**
     * 指定条件用Or查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function whereOr($field, $op, $condition = null): self
    {
        if ($condition === null) {
            $condition = $op;
            $op = '=';
        }
        if (isset($this->options['whereOr'])) {
            $whereOr = &$this->options['whereOr'];
        }
        $whereOr[$field] = [$op, $condition];
        $this->options['whereOr'] = $whereOr;
        return $this;
    }

    /**
     * 按时间搜索
     * @param      $field
     * @param      $op
     * @param null $range
     * @return $this
     */
    public function whereTime($field, $op, $range = null): self
    {

        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        $where = &$this->options['where'];
        $where[$field] = [$op, $range];
        return $this;
    }

    /**
     * 缓存
     * @param bool $key
     * @param null $expire
     * @param null $tag
     * @return $this
     */
    public function cache(bool $key = true, $expire = null, $tag = null): self
    {
        $this->options['cache'] = ['key' => $key, 'expire' => $expire, 'tag' => $tag];
        return $this;
    }

    /**
     * 按列获取
     * @param string $field
     * @param string $keyfield
     * @return array|false|string
     */
    public function rawColumn($field = '', $keyfield = '')
    {
        return $this->column($this->options['where'] ?? null, $field, $keyfield);
    }

    /**
     * 获取 指定的值
     * @param $field
     * @return int|mixed|string|null
     */
    public function rawValue($field)
    {
        if (empty($this->options['where'])) {
            return false;
        }
        return $this->value($this->options['where'], $field);
    }


    /**
     * 插入记录
     * @param array $data 更新的数据
     * @return int|string
     */
    public function rawUpdate($data)
    {
        // 没有数据，没有条件时，直接返回false
        if (empty($this->options['where']) || empty($data)) {
            return false;
        }
        return $this->update($this->options['where'], $data);
    }

    /**
     * 删除记录
     * @return int
     */
    public function rawDelete()
    {
        if (empty($this->options['where'])) {
            return false;
        }
        return $this->delete($this->options['where']);
    }

    /**
     * 生成调用的参数
     * @param $arguments
     * @return array
     */
    protected function makeParams($arguments): array
    {
        if (empty($this->options)) {
            $params = $arguments;
        } else {
            $params = $this->options;
            if (!empty($arguments)) {
                foreach ($arguments as $id => $k) {
                    if (is_array($k)) {
                        $params[key($k)] = current($k);
                    } else {
                        $params[$id] = $k;
                    }
                }
            }
        }
        return $params;
    }

}
