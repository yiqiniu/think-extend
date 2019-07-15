<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace yiqiniu\db\connector;

use PDO;
use think\db\Connection;

/**
 * Pgsql数据库驱动
 */
class Pgsql extends Connection
{
    protected $builder = '\\think\\db\\builder\\Pgsql';


    //自增ID的对应 seq名称
    protected $_seq = '';
    protected $_schema = '';

    // PDO连接参数
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * 取得数据表的字段信息
     * @access public
     * @param  string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        //$sql             = 'select fields_name as "field",fields_type as "type",fields_not_null as "null",fields_key_name as "key",fields_default as "default",fields_default as "extra" from table_msg(\'' . $tableName . '\');';

        $sql = "select a.attname as \"field\",
            t.typname as \"type\",
            a.attnotnull as \"null\",
            i.indisprimary as \"key\",
            d.adsrc as \"default\",
            d.adsrc as \"extra\"
            from pg_class c
            inner join pg_attribute a on a.attrelid = c.oid
            inner join pg_type t on a.atttypid = t.oid
            left join pg_attrdef d on a.attrelid=d.adrelid and d.adnum=a.attnum
            left join pg_index i on a.attnum=ANY(i.indkey) and c.oid = i.indrelid
            where (c.relname='{$tableName}' or c.relname = lower('{$tableName}'))   AND a.attnum > 0
                order by a.attnum asc;";

        $pdo = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $info[$val['field']] = [
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)('' !== $val['null']),
                    'default' => $val['default'],
                    'primary' => !empty($val['key']),
                    'autoinc' => (0 === strpos($val['extra'], 'nextval(')),
                ];
            }
        }
        $this->getseqField($info);
        return $this->fieldCase($info);
    }

    /**
     * 获取自增字段,用于获取插入后自增值
     * @param $list 字段列表
     */
    public function getseqField($list)
    {
        foreach ($list as $k => $v) {
            if ($v['autoinc']) {
                $arr = explode("'", $v['default']);
                $this->_seq = count($arr) == 3 ? $arr[1] : '';
                break;
            }
        }
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param  string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
        $pdo = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param string $sequence 自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {

        try {
            return $this->linkID->lastInsertId($sequence);
        } catch (Throwable $e) {
            if (strpos($e, 'SQLSTATE[55000]') != false && strpos($e, 'lastval') != false) {
                return '';
            }
        }


    }

    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param  array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'pgsql:dbname=' . $config['database'] . ';host=' . $config['hostname'];

        if (!empty($config['hostport'])) {
            $dsn .= ';port=' . $config['hostport'];
        }

        return $dsn;
    }

    /**
     * SQL性能分析
     * @access protected
     * @param  string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
        return [];
    }

    protected function supportSavepoint()
    {
        return true;
    }
}
