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
    /**
     * 执行存储过程
     * @param $sql string       要执行sql
     * @param mixed ...$argv 参数
     * @return mixed            返回执行的结果
     * @throws Exception
     */
    public function procedure($sql, ...$argv)
    {
        try {
            $sql = vsprintf($sql, $argv);
            //echo ($sql);
            // 取返回值变量
            $pos = strpos($sql, '@');
            $param = '';
            if ($pos !== false) {
                $param = substr($sql, $pos, strpos($sql, ')') - strlen($sql));
            }
            $this->startTrans();
            if (strpos($this->getConfig('type'), 'mysql') != false) {
                $bret = $this->execute($sql);
                if ($bret != false && !empty($param)) {
                    $bret = $this->query('select ' . $param)[0];
                }
            } else {
                $bret = $this->query($sql)[0];
            }
            $this->commit();
            return $bret;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}

