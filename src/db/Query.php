<?php


namespace yiqiniu\db;


use think\db\exception\DataNotFoundException;
use think\db\exception\DbException as Exception;
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
     * 分页查询
     * @access public
     * @param int|array $listRows 每页数量 数组表示配置参数
     * @param int|bool $simple 是否简洁模式或者总记录数
     * @return Array
     * @throws Exception
     */
    public function paginateArray($listRows = null, $simple = false): Array
    {
        if (is_int($simple)) {
            $total = $simple;
            $simple = false;
        }

        $defaultConfig = [
            'query' => [], //url额外参数
            'fragment' => '', //url锚点
            'var_page' => 'page', //分页变量
            'list_rows' => 15, //每页数量
        ];

        if (is_array($listRows)) {
            $config = array_merge($defaultConfig, $listRows);
            $listRows = intval($config['list_rows']);
        } else {
            $config = $defaultConfig;
            $listRows = intval($listRows ?: $config['list_rows']);
        }

        $page = isset($config['page']) ? (int)$config['page'] : Paginator::getCurrentPage($config['var_page']);

        $page = $page < 1 ? 1 : $page;

        $config['path'] = $config['path'] ?? Paginator::getCurrentPath();

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

        return Paginator::make($results, $listRows, $page, $total, $simple, $config);
    }


    /**
     * 根据数字类型字段进行分页查询（大数据）
     * @access public
     * @param int|array $listRows 每页数量或者分页配置
     * @param string $key 分页索引键
     * @param string $sort 索引键排序 asc|desc
     * @return Array
     * @throws Exception
     */
    public function paginateXArray($listRows = null, string $key = null, string $sort = null): Array
    {
        $defaultConfig = [
            'query' => [], //url额外参数
            'fragment' => '', //url锚点
            'var_page' => 'page', //分页变量
            'list_rows' => 15, //每页数量
        ];

        $config = is_array($listRows) ? array_merge($defaultConfig, $listRows) : $defaultConfig;
        $listRows = is_int($listRows) ? $listRows : (int)$config['list_rows'];
        $page = isset($config['page']) ? (int)$config['page'] : Paginator::getCurrentPage($config['var_page']);
        $page = $page < 1 ? 1 : $page;

        $config['path'] = $config['path'] ?? Paginator::getCurrentPath();

        $key = $key ?: $this->getPk();
        $options = $this->getOptions();

        if (is_null($sort)) {
            $order = $options['order'] ?? '';
            if (!empty($order)) {
                $sort = $order[$key] ?? 'desc';
            } else {
                $this->order($key, 'desc');
                $sort = 'desc';
            }
        } else {
            $this->order($key, $sort);
        }

        $newOption = $options;
        unset($newOption['field'], $newOption['page']);

        $data = $this->newQuery()
            ->options($newOption)
            ->field($key)
            ->where(true)
            ->order($key, $sort)
            ->limit(1)
            ->find();

        $result = $data[$key];

        if (is_numeric($result)) {
            $lastId = 'asc' == $sort ? ($result - 1) + ($page - 1) * $listRows : ($result + 1) - ($page - 1) * $listRows;
        } else {
            throw new Exception('not support type');
        }

        $results = $this->when($lastId, function ($query) use ($key, $sort, $lastId) {
            $query->where($key, 'asc' == $sort ? '>' : '<', $lastId);
        })
            ->limit($listRows)
            ->selectArray();

        $this->options($options);

        return Paginator::make($results, $listRows, $page, null, true, $config);
    }

    /**
     * 执行存储过程
     * @param $sql string       要执行sql
     * @param mixed ...$argv 参数
     * @return mixed            返回执行的结果
     * @throws Exception
     */
    public function execute_procedure($sql, ...$argv)
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
            if (stripos($this->getConfig('type'), 'mysql') != false) {
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

