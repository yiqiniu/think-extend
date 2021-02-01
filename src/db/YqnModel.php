<?php


namespace yiqiniu\extend\db;


use think\Exception;
use think\facade\Db;
use yiqiniu\extend\db\traits\ModelParams;
use yiqiniu\extend\db\traits\ReidsCache;


/**
 * 基本Query的常用函数
 * Class BaseModel
 * @package yiqiniu\db
 * @method  insertAll(array $dataSet = [], int $limit = 0)
 * @method  selectInsert(array $fields, string $table)
 * @method  cache($key = true, $expire = null, $tag = null)
 * @method  alias($alias)
 * @method  getLastInsID(string $sequence = null)
 * @method  getLastSql()
 * @method  startTrans()
 * @method  commit()
 * @method  rollback()
 * @method  count(string $field = '*');
 */
class YqnModel
{

    use ReidsCache;
    use ModelParams;

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
    private $fields = [];

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
        'where_or' => [],
        'order' => '',
        'group' => '',
        'field' => '',
        'limit' => 0,
        'having' => '',
        'page_size' => 30,
        'cache' => [],
        'without_field' => '',
        'alias' => '',
        'join' => [],
    ];


    /**
     * 表映射
     * @var array
     */
    protected $table_map = [];


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
     * 生成Where条件的Db
     * @return \think\facade\Db
     */
    protected function makeWhereDb($conditions = [])
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
        // 解析 or
        if (!empty($this->options['where_or'])) {
            $db = $db->whereOr($this->parseWhere($this->options['where_or']));
        }
        // 解析原始条件
        if (!empty($this->options['where_raw'])) {
            foreach ($this->options['where_raw'] as $subsql) {
                $db = $db->whereRaw($subsql);
            }
        }
        // 解析日期类型
        if (!empty($this->options['where_time'])) {
            foreach ($this->options['where_time'] as $date) {
                [$field, $op, $range] = $date;
                $db = $db->whereTime($field, $op, $range);
            }
        }
        //处理闭包搜索
        if (!empty($this->options['where_function']) && !empty($this->table_map)) {
            $function = is_array(current($this->options["where_function"])) ? $this->options["where_function"] : [$this->options["where_function"]];
            foreach ($function as $field => $v) {
                if (empty($v['table']) || empty($v['field']) || empty($this->table_map[$v['table']])) {
                    continue;
                }
                $name = $this->table_map[$v['table']];
                $db = $db->where($field, $v['op'] ?? 'in', function ($query) use ($name, $v) {
                    $query->name($name)->where($this->parseWhere($v['where']))->field($v['field']);
                });
            }
        }

        return $db;
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

        $db = $this->makeWhereDb();

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
        //添加别别名的处理
        if (!empty($this->options['alias'])) {
            $db = $db->alias($this->options['alias']);
        }

        //处理关联
        if (!empty($this->options['join']) && is_array($this->options["join"])) {

            if (empty($this->options['alias'])) {
                $db = $db->alias('u');
            }
            // 将join 条件转为数组
            $joins = is_array(current($this->options["join"])) ? $this->options["join"] : [$this->options["join"]];
            foreach ($joins as $k => $item) {
                switch (count($item)) {
                    case 1:
                        $db = $db->join($item[0]);
                        break;
                    case 2:
                        $db = $db->join($item[0], $item[1]);
                        break;
                    case 3:
                        $db = $db->join($item[0], $item[1], $item[2]);
                        break;
                    default:
                        break;
                }
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
     * @throws Exception
     */
    public function select($conditions = [])
    {
        $result = $this->makeOptionDb($conditions)->select();

        // 使用select_after 处理数据
        $this->triggerEvent('select_after', [&$result]);

        return $result;
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
        $result = $this->makeOptionDb($conditions)->find();


        // 使用select_after 处理数据
        if ($result !== null) {

            $list = [$result];
            $this->triggerEvent('select_after', [&$list]);
            $result = current($list);
            unset($list);
        }
        return $result;
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
        $db = $this->makeOptionDb($options);
        if ($this->options["page_size"] <= 0) {
            $this->options["page_size"] = self::DEFAULT_PAGE_SIZE;
        }
        if ($this->options["page_size"] > 100) {
            $this->options["page_size"] = self::DEFAULT_PAGE_SIZE;
        }
        $result = $db->paginate($this->options["page_size"]);

        // 使用select_after 处理数据
        $this->triggerEvent('select_after', [&$result['data']]);

        return $result;

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
        if (!empty($where)) {
            $this->options['where'] = $where;
        }
        return $this->makeWhereDb()->value($field);
    }


    /**
     *
     * @param array $data
     * @param bool $getLastInsID
     * @return int|string
     * @throws Exception
     */
    public function insert(array $data = [], bool $getLastInsID = false)
    {
        try {
            $this->triggerEvent('insert_before', [&$data]);

            $result = $this->db()->insert($data, $getLastInsID);

            $this->triggerEvent('insert_after', [$data, $result]);
            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 插入记录并获取插入的ID
     * @param array $data
     * @return int|string
     * @throws Exception
     */
    public function insertGetId(array $data)
    {
        return $this->insert($data, true);
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
        try {
            if (empty($data)) {
                api_exception(API_ERROR, '修改数据不能为空');
            }
            if (!empty($where)) {
                $this->options['where'] = $where;
            }

            $this->triggerEvent('update_before', [&$where, &$data]);

            $result = $this->makeWhereDb()->update($data);

            $this->triggerEvent('update_after', [$where, $data, $result]);

            return $result;
        } catch (Exception $e) {
            throw $e;
        }


    }

    /**
     * 删除记录
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function delete($where = [])
    {
        try {
            if (!empty($where)) {
                $this->options['where'] = $where;
            }
            $this->triggerEvent('delete_before', [&$where]);

            $result = $this->makeWhereDb()->delete();

            $this->triggerEvent('delete_after', [$where, $result]);
            return $result;
        } catch (Exception $e) {
            throw $e;
        }

    }

    public function getPk()
    {
        return $this->pk;
    }

    /**
     * 触发事件
     * @param string $event
     * @param array $params
     * @return mixed
     */
    protected function triggerEvent(string $event, array $params)
    {
        try {
            if (method_exists($this, $event)) {
                return call_user_func_array([$this, $event], $params);
            }
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
        foreach ($where as $key => $values) {

            // 去掉无效的条件，
            //1.key不是字符串类型， 值也不是数组，问题无法区分字段和类型，
            //2.values 为空的
            if ((is_numeric($key) && !is_array($values)) || (empty($values) && $values !== '0')) {
                unset($where[$key]);
                continue;
            }
            // 兼容格式：
            // 1.['id','=',3]
            // 2.['id',3]
            // 3.[id=>['in'=>['11']]]
            // 4. id:['in','1,2,3,4']
            if (is_numeric($key) && is_array($values)) {
                if (count($values) === 2) {
                    $field = array_shift($values);
                    [$op, $value] = current($values);
                    $where[$field] = [$field, $op, $value];
                } else if (count($values) === 3) {
                    [$field, $op, $value] = $values;
                    $where[$field] = [$field, $op, $value];
                } else {
                    $field = array_keys($values)[0];
                    if (!is_numeric($field)) {
                        $values = current($values);
                        if (is_array($values) && count($values) === 2) {
                            [$op, $value] = current($values);
                            $where[$field] = [$field, '=', $value];
                        } else {
                            $op = array_keys($values)[0];
                            $op = is_numeric($op) ? '=' : $op;
                            $value = current($values);
                        }
                        $where[$field] = [$field, $op, $value];
                    }
                }
                unset($where[$key]);
                continue;
            }
            $field = $key;
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
        return call_user_func_array([$this->makeOptionDb(), $method], $arguments);
    }
}