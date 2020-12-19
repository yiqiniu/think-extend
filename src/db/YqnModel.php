<?php


namespace yiqiniu\extend\db;


use think\Exception;
use think\facade\Db;
use yiqiniu\extend\traits\MergeParams;
use yiqiniu\extend\traits\ReidsCache;


/**
 * 基本Query的常用函数
 * Class BaseModel
 * @package yiqiniu\db
 * @method  insert(array $data = [], bool $getLastInsID = false)
 * @method  insertGetId(array $data)
 * @method  insertAll(array $dataSet = [], int $limit = 0)
 * @method  selectInsert(array $fields, string $table)
 * @method  cache($key = true, $expire = null, $tag = null)
 * @method  alias($alias)
 * @method  getLastInsID(string $sequence = null)
 * @method  getLastSql()
 * @method  startTrans()
 * @method  commit()
 * @method  rollback()
 */
class YqnModel
{

    use ReidsCache;
    use MergeParams;

    //默认缓存时间
    const DEFAULT_CACHE_TIME = 300;

    //默认page_size 为30
    const DEFAULT_PAGE_SIZE = 30;

    /**
     * 指定表名
     * @var string
     */
    protected $name = '';


    //表字段
    private $fields=[];

    /**
     * @var string
     * 表的主键
     */
    protected $pk = 'id';

    /**
     * 当前查询参数
     * @var array
     */
    protected $options = [];

    // 默认
    private $def = [
        'where' => [],
        'order' => '',
        'group' => '',
        'field' => '',
        'limit' => 0,
        'having' => '',
        'page_size' => 30,
        'cache' => [],
        'without_field' => ''
    ];


    public function __construct()
    {
        $this->name = parse_name($this->name, 1);
    }

    /**
     * 外部调用的初始变量
     * @param $args
     */
    public function _initilize($args)
    {
        $this->removeOption();
        $this->db()->removeOption();
    }

    /**
     * 获取当前的处理类
     * @param string $name
     * @return Db
     */
    protected function db(string $name = null)
    {
        return Db::name(empty($name) ? $this->name : $name);
    }


    /**
     * 根据查询条件生成DB对象
     * @param array $conditions
     * @return Db
     */
    protected function makeOptionDb($conditions = [])
    {
        if (!empty($conditions)) {
            $this->options = array_merge($this->def, $conditions);
        } else {
            $this->options = array_merge($this->def, $this->options);
        }

        $db = $this->db();

        if (!empty($this->options['where'])) {
            $db = $db->where($this->parseWhere($this->options['where']));
        }
        if (!empty($this->options['order'])) {
            $db = $db->order($this->options['order']);
        }
        if (!empty($this->options['group'])) {
            $db = $db->group($this->options['group']);
        }
        if (!empty($this->options['field'])) {
            $db = $db->field($this->options['field']);
        }
        //排除字段
        if (!empty($this->options['without_field'])) {
            $db = $db->withoutField($this->options['without_field']);
        }
        if ($this->options['limit'] > 0) {
            if ($this->options["limit"] > 1000) {
                $this->options["limit"] = 1000;
            }
            $db = $db->limit((int)$this->options['limit']);
        }
        //处理having
        if (!empty($this->options['having'])) {
            $db = $db->having($this->options['having']);
        }
        //处理缓存
        if (!empty($this->options['cache'])) {
            $cache = &$this->options['cache'];
            $db = $db->cache($cache['key'], $cache['expire'], $cache['tag']);
        }
        //处理关联
        if (!empty($this->options['join']) && is_array($this->options["join"])) {
            $db->alias('u');
            if (is_array($this->options["join"][0])) {
                $join = [];
                foreach ($this->options["join"] as $j) {
                    $join[] = [$j[0], $j[1], (empty($j[2]) ? 'left' : $j[2])];
                }
                $db->join($join);
            } else {
                $db->join([$this->options["join"]]);
            }
        }
        return $db;
    }

    /**
     * 返回列表
     * @param array $conditions
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function select($conditions = [])
    {
        return $this->makeOptionDb($conditions)->select();
    }

    /**
     * 返回一条记录
     * @param array $conditions
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function find($conditions = [])
    {
        return $this->makeOptionDb($conditions)->find();
    }


    /**
     * 分页
     * @param array $options
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function page($options = []): array
    {
        $db = $this->makeOptionDb($options);
        if ($this->options["page_size"] <= 0) {
            $this->options["page_size"] = self::DEFAULT_PAGE_SIZE;
        }
        if ($this->options["page_size"] > 100) {
            $this->options["page_size"] = self::DEFAULT_PAGE_SIZE;
        }
        return $db->paginate($this->options["page_size"])->toArray();
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
        if (!empty($where)) {
            $this->options['where'] = $where;
        }
        return $this->makeOptionDb()->column($field, $keyfield);
    }

    /**
     * 获取 指定的值
     * @param $where
     * @param $field
     * @return int|mixed|string|null
     */
    public function value($where, $field)
    {
        return $this->db()->where($where)->value($field);
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
        //修改数据错误
        if (empty($data)) {
            return false;
        }
        return $this->db()->where($where)->update($data);
    }

    /**
     * 删除记录
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function delete($where)
    {
        return $this->db()->where($where)->delete();
    }


    /**
     * 执行存储过程
     * @param       $sql string       要执行sql
     * @param mixed ...$argv 参数
     * @return mixed            返回执行的结果
     * @throws Exception
     */
    public function exec_procedure($sql, ...$argv)
    {
        try {
            $sql = vsprintf($sql, $argv);
            // 取返回值变量
            $pos = strpos($sql, '@');
            $param = '';
            if ($pos !== false) {
                $param = substr($sql, $pos, strpos($sql, ')') - strlen($sql));
            }
            $bret = $this->db()->execute($sql);
            if ($bret !== false && !empty($param)) {
                $bret = $this->db()->query('select ' . $param)[0];
            }
            return $bret;
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 处理where条件
     * @param array $where_array
     * @return array
     */
    protected function parseWhere($where = [])
    {

        //将全部条件整理到where 数组中
        foreach ($where as $field => $values) {
            if (empty($values) && $values !== '0') {
                continue;
            }
            if (is_array($values)) {
                [$op, $value] = $values;
                $where[$field] = [$field, $op, $value];
            } else {
                $where[$field] = [$field, '=', $values];
            }
        }

        //产生查询条件
        foreach ($where as $field => &$values) {
            // 如果自定义函数的话,使用自定义处理
            $field_function = 'where' . ucfirst($field);
            if (method_exists($this, $field_function)) {
                if ($function_result = $this->$field_function($field, is_array($values) ? end($values) : $values)) {
                    $where[$field] = $function_result;
                } else {
                    unset($where[$field]);
                }
            } else {
                //处理第一个不是字段的问题
                if ($field != current($values)) {
                    if (is_array($values)) {
                        [$op, $value] = $values;
                        $where[$field] = [$field, $op, $value];
                    } else {
                        $where[$field] = [$field, '=', $values];
                    }
                }

            }
        }

        return array_values($where);
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->db(), $method], $arguments);
    }
}