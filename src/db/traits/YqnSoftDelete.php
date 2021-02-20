<?php


namespace yiqiniu\extend\db\traits;

use think\Exception;

/**
 * 软删除
 * Trait SoftDelete
 * @package yiqiniu\extend\db\traits
 */
trait YqnSoftDelete
{


    /**
     * 返回列表
     * @param array $conditions
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws Exception
     */
    public function select($conditions = [])
    {
        if ($delete_field = $this->getDeleteField(true)) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::select($conditions);
    }

    /**
     * 返回一条记录
     * @param array $conditions
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws Exception
     */
    public function find($conditions = [])
    {

        if ($delete_field = $this->getDeleteField()) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::find($conditions);
    }


    /**
     * 分页
     * @param array $options
     * @return array
     * @throws \think\db\exception\DbException
     * @throws Exception
     */
    public function page($options = []): array
    {

        if ($delete_field = $this->getDeleteField()) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::page($options);
    }


    /**
     * 按列获取
     * @param array $where
     * @param string $field
     * @param string $keyfield
     * @return array|false|string
     */
    public function column($where = null, $field = '', $keyfield = '')
    {
        if ($delete_field = $this->getDeleteField()) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::column($where, $field, $keyfield);

    }

    /**
     * 获取 指定的值
     * @param $where
     * @param $field
     * @return int|mixed|string|null
     */
    public function value($where, $field)
    {

        if ($delete_field = $this->getDeleteField()) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::value($where, $field);
    }


    /**
     * 插入记录
     * @param array $where
     * @param array $data 更新的数据
     * @return int|string
     * @throws \think\db\exception\DbException
     */
    public function update($where, $data)
    {

        if ($delete_field = $this->getDeleteField()) {
            $this->options['where'][] = [$delete_field, $this->getWithTrashedExp()];
        }
        return parent::update($where, $data);
    }

    /**
     * 删除记录
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function delete($where = [])
    {
        if ($delete_field = $this->getDeleteField()) {
            $this->setOption(['soft_delete' => [$delete_field, $this->getSoftDeleteValue()]]);
        }
        return parent::delete($where);
    }

    /**
     * 获取软删除字段
     * @access protected
     * @param bool $read 是否查询操作 写操作的时候会自动去掉表别名
     * @return string|false
     */
    protected function getDeleteField(bool $read = false)
    {
        return !empty($this->softDeleteField) ? $this->softDeleteField : '';
    }

    /**
     * 获取软删除数据的查询条件
     * @access protected
     * @return array|mixed
     * @example  时间类型：['null', '']  数据类型： ['<>', 1];
     */
    abstract protected function getWithTrashedExp();


    /**
     * 获取删除时，要更新到该记录的值
     * @access protected
     * @return mixed
     * @example 时间类型：date()  数据类型： 1;
     */
    abstract protected function getSoftDeleteValue();


}