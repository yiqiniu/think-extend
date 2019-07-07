<?php


namespace yiqiniu\facade;

use Generator;
use think\db\Connection;
use think\db\Raw;
use think\Facade;
use think\Paginator;
use yiqiniu\db\Query;

/**
 * @see \think\Db
 * @mixin \think\Db
 * @method object buildQuery(string $query, mixed $connection) static 创建一个新的查询对象
 * @method Query connect(array $config =[], mixed $name = false) static 连接/切换数据库连接
 * @method Connection getConnection() static 获取数据库连接对象
 * @method Query master() static 从主服务器读取数据
 * @method Query table(string $table) static 指定数据表（含前缀）
 * @method Query name(string $name) static 指定数据表（不含前缀）
 * @method Raw raw(string $value) static 使用表达式设置数据
 * @method Query where(mixed $field, string $op = null, mixed $condition = null) static 查询条件
 * @method Query whereRaw(string $where, array $bind = []) static 表达式查询
 * @method Query whereExp(string $field, string $condition, array $bind = []) static 字段表达式查询
 * @method Query when(mixed $condition, mixed $query, mixed $otherwise = null) static 条件查询
 * @method Query join(mixed $join, mixed $condition = null, string $type = 'INNER') static JOIN查询
 * @method Query view(mixed $join, mixed $field = null, mixed $on = null, string $type = 'INNER') static 视图查询
 * @method Query field(mixed $field, boolean $except = false) static 指定查询字段
 * @method Query fieldRaw(string $field, array $bind = []) static 指定查询字段
 * @method Query union(mixed $union, boolean $all = false) static UNION查询
 * @method Query limit(mixed $offset, integer $length = null) static 查询LIMIT
 * @method Query order(mixed $field, string $order = null) static 查询ORDER
 * @method Query orderRaw(string $field, array $bind = []) static 查询ORDER
 * @method Query cache(mixed $key = null , integer $expire = null) static 设置查询缓存
 * @method Query withAttr(string $name, callable $callback = null) static 使用获取器获取数据
 * @method mixed value(string $field) static 获取某个字段的值
 * @method array column(string $field, string $key = '') static 获取某个列的值
 * @method mixed find(mixed $data = null) static 查询单个记录
 * @method mixed select(mixed $data = null) static 查询多个记录
 * @method mixed selectArray(mixed $data = null) static 查询多个记录
 * @method mixed findArray(mixed $data = null) static 查询单个记录
 * @method integer save(boolean $forceInsert = false) static 保存记录 自动判断insert或者update
 * @method integer insert(array $data, boolean $getLastInsID = false, string $sequence = null) static 插入一条记录
 * @method integer insertGetId(array $data, string $sequence = null) static 插入一条记录并返回自增ID
 * @method integer insertAll(array $dataSet) static 插入多条记录
 * @method integer update(array $data) static 更新记录
 * @method integer delete(mixed $data = null) static 删除记录
 * @method boolean chunk(integer $count, callable $callback, string $column = null) static 分块获取数据
 * @method Generator cursor(mixed $data = null) static 使用游标查找记录
 * @method mixed query(string $sql, array $bind = [], boolean $master = false, bool $pdo = false) static SQL查询
 * @method integer execute(string $sql, array $bind = [], boolean $fetch = false, boolean $getLastInsID = false, string $sequence = null) static SQL执行
 * @method Paginator paginate(integer $listRows = 15, mixed $simple = null, array $config = []) static 分页查询
 * @method mixed transaction(callable $callback) static 执行数据库事务
 * @method void startTrans() static 启动事务
 * @method void commit() static 用于非自动提交状态下面的查询提交
 * @method void rollback() static 事务回滚
 * @method boolean batchQuery(array $sqlArray) static 批处理执行SQL语句
 * @method string getLastInsID(string $sequence = null) static 获取最近插入的ID
 * @method mixed getConfig(string $name = '') static 获取数据库的配置参数
 */
class Db extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'think\\db';
    }
}