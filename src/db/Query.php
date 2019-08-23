<?php


namespace yiqiniu\db;


use think\Container;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;
use think\Paginator;


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
    public function selectArray($data = null)
    {
        $this->parseOptions();
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
        $this->parseOptions();
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

    /**
     * 分页查询
     * @access public
     * @param int|array $listRows 每页数量 数组表示配置参数
     * @param int|bool $simple 是否简洁模式或者总记录数
     * @param array $config 配置参数
     *                            page:当前页,
     *                            path:url路径,
     *                            query:url额外参数,
     *                            fragment:url锚点,
     *                            var_page:分页变量,
     *                            list_rows:每页数量
     *                            type:分页类名
     * @return \think\Paginator
     * @throws DbException
     */
    public function paginateArray($listRows = null, $simple = false, $config = [])
    {
        if (is_int($simple)) {
            $total = $simple;
            $simple = false;
        }

        $paginate = Container::get('config')->pull('paginate');

        if (is_array($listRows)) {
            $config = array_merge($paginate, $listRows);
            $listRows = $config['list_rows'];
        } else {
            $config = array_merge($paginate, $config);
            $listRows = $listRows ?: $config['list_rows'];
        }

        /** @var Paginator $class */
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\paginator\\driver\\' . ucwords($config['type']);
        $page = isset($config['page']) ? (int)$config['page'] : call_user_func([
            $class,
            'getCurrentPage',
        ], $config['var_page']);

        $page = $page < 1 ? 1 : $page;

        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);

        if (!isset($total) && !$simple) {
            $options = $this->getOptions();

            unset($this->options['order'], $this->options['limit'], $this->options['page'], $this->options['field']);

            $bind = $this->bind;
            $total = $this->count();
            $results = $this->options($options)->bind($bind)->page($page, $listRows)->selectArray();
        } elseif ($simple) {
            $results = $this->limit(($page - 1) * $listRows, $listRows + 1)->selectArray();
            $total = null;
        } else {
            $results = $this->page($page, $listRows)->selectArray();
        }

        $this->removeOption('limit');
        $this->removeOption('page');

        return $class::make($results, $listRows, $page, $total, $simple, $config);
    }
}

