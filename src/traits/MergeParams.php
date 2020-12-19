<?php

namespace yiqiniu\extend\traits;

trait MergeParams
{

    /**
     * 当前查询参数
     * @var array
     */
    protected $options = [];


    /**
     * 移除上次的设置
     * @return $this
     */
    public function removeOption()
    {
        $this->options = [];
        return $this;
    }

    /**
     * 批量设置参数
     * @param array $option 要设置的参数
     * @return $this
     */
    public function setOption($option)
    {
        if (!empty($option)) {
            $this->options = array_merge($this->options, $option);
        }
        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param mixed $length 查询数量
     * @return $this
     */
    public function limit($length = null)
    {
        $this->options['limit'] = $length;
        return $this;
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc'])
     * @access public
     * @param string|array $field 排序字段
     * @param string $order 排序
     * @return $this
     */
    public function order($order)
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
     * @param boolean $except 是否排除
     * @return $this
     */
    public function field($field)
    {
        if (!empty($field)) {
            $this->options['field'] = $field;
        }
        return $this;
    }

    /**
     * 返指定字段进行分组操作
     * @param $field
     * @return $this
     */
    public function group($field)
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
     * 页数
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function setpage(int $page, int $pageSize)
    {
        if (!empty($page)) {
            $this->options['page'] = $page;
            $this->options['pagesize'] = $pageSize;
        }
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
    public function where($field, $op = null, $condition = null)
    {

        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        $where = &$this->options['where'];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $where[$key] = is_array($value) ? $value : ['=', $value];
            }
        } else {
            if ($condition == null) {
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
    public function whereIn($field, $condition)
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
    public function whereOr($field, $op, $condition = null)
    {
        if ($condition == null) {
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
}
