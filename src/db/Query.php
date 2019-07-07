<?php


namespace yiqiniu\db;


use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;


/**
 * 数据查询类
 */
class Query extends \think\db\Query
{


    /**
     * 查找记录 返回数组类型
     * @access public
     * @param mixed $data 数据
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function selectArray($data = null): array
    {
        if (!is_null($data)) {
            // 主键条件分析
            $this->parsePkWhere($data);
        }

        return $this->connection->select($this);
    }

    /**
     * 查找单条记录 返回数组类型
     * @access public
     * @param mixed $data 查询数据
     * @return array|Model|null
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function findArray($data = null)
    {
        if (!is_null($data)) {
            // AR模式分析主键条件
            $this->parsePkWhere($data);
        }

        return $this->connection->find($this);

    }
}

