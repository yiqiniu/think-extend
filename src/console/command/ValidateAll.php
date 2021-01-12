<?php


namespace yiqiniu\extend\console\command;


use think\App;
use think\console\command\Make;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * Class ModelAll
 * @package yiqiniu\console\command
 */
class ValidateAll extends Make
{

    protected $type = 'Command';


    protected $app = null;
    // 不能当做类名的表名

    protected $stubs = [
        'validate' => 'validate',
    ];

    // 是否全部字段 , false 为不为空的字段,true 全部字段
    protected $allfield = false;

    //是否pgsql数据库
    protected $is_postgressql = false;

    // 数据库架构名,PGsql 有效
    protected $schema_name = 'public';

    protected function configure()
    {
        $this->setName('make:validateall')
            ->addOption('all', '-a', Option::VALUE_NONE, "Make All Fields")
            ->addOption('force', '-f', Option::VALUE_NONE, "force update")
            ->addOption('schema', '-s', Option::VALUE_REQUIRED, "specified schema name")
            ->addOption('module', '-m', Option::VALUE_REQUIRED, "specified Module name")
            ->addOption('table', '-t', Option::VALUE_REQUIRED, "specified table name")
            ->addOption('keyword', '-k', Option::VALUE_REQUIRED, "specified table name keyword")
            ->addOption('subdirectory', '-d', Option::VALUE_REQUIRED, "specified SubDirectories")
            ->setDescription('Generate all validations based on database table fields');
    }


    protected function execute(Input $input, Output $output)
    {

        // 关键字
        $keyword = $input->getOption('keyword');

        // 子目录
        $subDir = strtolower($input->getOption('subdirectory'));

        //强制更新
        $force_update = $input->getOption('force');
        //全部字段
        $this->allfield = $input->getOption('all');
        // 指定schema
        $schema = $input->getOption('schema');
        // 指定模块
        $module = $input->getOption('module');

        $this->app = App::getInstance();
        $default = $this->app->config->get('database.default', '');
        if (!empty($default)) {
            $connect = $this->app->config->get('database.connections.' . $default);
        } else {
            $connect = $this->app->config->get('database.');
        }

        if (empty($connect['database'])) {
            $this->output->error('database not  setting.');
            return;
        }

        $table_name = trim($input->getOption('table'));
        if (!empty($table_name)) {
            // 生成所有的类
            $prefix_len = strlen($connect['prefix']);
            if (substr($table_name, 0, $prefix_len) != $connect['prefix']) {
                $table_name = $connect['prefix'] . $table_name;
            }
        }

        $map_tablename = [];
        $this->is_postgressql = stripos($connect['type'], 'pgsql') !== false;
        $db_connect = Db::connect($default ?: $connect);
        if ($this->is_postgressql != false) {
            if (!empty($table_name)) {
                $map_tablename = ['tablename' => $table_name];
            }
            if (!empty($schema)) {
                $this->schema_name = $schema;
            }
            $tablelist = $db_connect->table('pg_class')
                ->field(['relname as name', "cast(obj_description(relfilenode,'pg_class') as varchar) as comment"])
                ->where('relname', 'in', function ($query) use ($map_tablename) {
                    $query->table('pg_tables')
                        ->where('schemaname', $this->schema_name)
                        ->where($map_tablename)
                        ->whereRaw("position('_2' in tablename)=0")->field('tablename');
                })->select();
        } else {
            if (!empty($table_name)) {
                $map_tablename = ['table_name' => $table_name];
            }
            $tablelist = $db_connect->table('information_schema.tables')
                ->where('table_schema', $connect['database'])
                ->where($map_tablename)
                ->field('table_name as name,table_comment as comment')
                ->select();
        }

        //select table_name,table_comment from information_schema.tables where table_schema='yiqiniu_new';

        $apppath = $this->app->getAppPath();
        if (!empty($module)) {
            $dirname = $apppath . $module . DIRECTORY_SEPARATOR . 'validate';
        } else {
            $dirname = $apppath . 'validate';
        }
        $dirname .= DIRECTORY_SEPARATOR;

        // 保存到子目录中
        if (!empty($subDir)) {
            $dirname .= strtolower($subDir) . DIRECTORY_SEPARATOR;
        }

        if (!file_exists($dirname) && !mkdir($dirname, 0644, true) && !is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
        // 获取生成空间的名称
        $namespace = $this->getNamespace2($module, $subDir);

        // 判断 是否有基本BaseModel

        $stubs = $this->getStub();


        // 生成所有的类
        $prefix_len = strlen($connect['prefix']);

        $model_stub = file_get_contents($stubs['validate']);

        // table 类用于获取字段
        $dbs = Db::connect($default ?: $connect);

        foreach ($tablelist as $k => $table) {

            // 处理关键字
            if (!empty($keyword) && stripos($table['name'], $keyword) === false) {
                continue;
            }
            $class_name = $this->parseName(substr($table['name'], $prefix_len), 1, true);
            // 处理关键字
            if (!empty($keyword) && stripos($table['name'], $keyword) === false) {
                continue;
            }
            // 如果是表名是class的改为ClassModel
            $filedinfo = $this->getTablesField($dbs, $table['name']);
            $model_file = $dirname . $class_name . 'Valid.php';
            if (!file_exists($model_file) || $force_update) {
                file_put_contents($model_file, str_replace(['{%namespace%}', '{%className%}', '{%comment%}', '{%rule%}', '{%message%}','{%fields%}'], [
                    $namespace,
                    $class_name,
                    $table['comment'],
                    $filedinfo['rule'],
                    $filedinfo['message'],
                    $filedinfo['fields'],
                ], $model_stub));
            }

        }


        $output->writeln('<info>' . $this->type . ':' . 'All Table Validate created successfully.</info>');


    }


    /**
     * 获取表的字段
     */
    public function getTablesField($db, $tablename)
    {

        if ($this->is_postgressql) {


            $sql = "SELECT 
            a.attname as field,
            format_type(a.atttypid,a.atttypmod) as type,
            col_description(a.attrelid,a.attnum) as comment,
            a.attnotnull as notnull   
            FROM pg_class as c,pg_attribute as a 
            where c.relname = '$tablename' and a.attrelid = c.oid and a.attnum>0;";

        } else {

            $sql = 'SHOW FULL COLUMNS FROM	' . $tablename;

        }

        $fields = $db->query($sql);

        // 生成模板
        $templates = [
            'rule' => "'%s'=>'%s',\r\n\t\t",
            'message' => "'%s.%s'=>'%s',\r\n\t\t",
            'default' => "'%s'=>'%s',\r\n\t\t",
            'fields' =>"*  字段：'%s'，\t类型：'%s', \t是否为空：%s, \t说明：%s \r\n\t",
        ];
        //返回值
        $retdata = [
            'rule' => '',
            'message' => '',
            'fields'=>''
        ];
        //忽略ID
        //$ignorefield = ['id', 'bz', 'memo', 'createdate', 'createtime', 'remark', 'status', 'zt'];
        $ignorefield = ['id', 'bz', 'memo', 'createdate', 'createtime', 'remark', 'status', 'zt'];
        //生成字段
        $no_valid_field=[
            'rule'=>'',
            'message'=>'',
        ];


        foreach ($fields as $field) {

            $field['field'] = $field['field'] ?? $field['Field'];
            $field['type'] = $field['type'] ?? $field['Type'];
            $field['notnull'] = $field['notnull'] ?? $field['Null'];
            $comment = $field['comment'] ?? ($field['Comment']??'');
            $field['comment'] = explode(' ',$comment)[0];
            $retdata['fields'].=sprintf($templates['fields'],$field['field'],$field['type'],$field['notnull'],$comment);

            $field_val = strtolower($field['field']);
            if ($field['type'] === '-'){
                continue;
            }
            $rule_list=[];
            $msg_list=[];
            if ((is_bool($field['notnull']) && $field['notnull']) ||
                stripos($field['notnull'], 'true') ||
                stripos($field['notnull'], 'yes')
            ){
                $rule_list[]='require';
                $msg_list[]=sprintf($templates['message'],$field['field'],'require',$field['comment'].'不能为空');

            }
            //字数类型
            if (stripos($field['type'], 'int') !== false) {
                $rule_list[]='number';
                $msg_list[]=sprintf($templates['message'],$field['field'],'number',$field['comment'].'只能为数字类型');
            }
            //日期
            if (stripos($field['type'], 'time') !== false || stripos($field['type'], 'date') !== false) {
                $rule_list[]='date';
                $msg_list[]=sprintf($templates['message'],$field['field'],'date',$field['comment'].'只能为日期类型');
            }
            //浮点
            if (stripos($field['type'], 'float') !== false || stripos($field['type'], 'numeric') !== false ||
                stripos($field['type'], 'decimal') !== false) {
                $rule_list[]='float';
                $msg_list[]=sprintf($templates['message'],$field['field'],'float',$field['comment'].'只能为数字可带小数点');
            }

            if(!empty($rule_list)){
                $retdata['rule'] .= sprintf($templates['rule'],$field['field'],(implode('|',array_unique($rule_list))));
                $retdata['message'] .= implode("",array_unique($msg_list));
            }else{
                $no_valid_field['rule'] .= '//'.sprintf($templates['rule'],$field['field'],(implode('|',array_unique($rule_list))));
                $no_valid_field['message'] .='//'.sprintf($templates['default'],$field['field'],$field['comment']);
            }
        }
     /*   $retdata['rule'].=$no_valid_field['rule'];
        $retdata['message'].=$no_valid_field['message'];*/
        return $retdata;
    }

    /**
     * 生成目录类的命令空间
     * @param $app
     * @param string $subdir
     * @return string
     */
    protected function getNamespace2($app, $subdir = '')
    {
        return 'app' . (empty($app) ? '' : ('\\' . $app)) . '\\validate' . (empty($subdir) ? '' : '\\' . $subdir);
    }

    protected function isPgsql()
    {

    }

    protected function getStub()
    {

        foreach ($this->stubs as $key => $filename) {

            $this->stubs[$key] = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $filename . '.stub';
        }
        return $this->stubs;
    }


    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @access public
     * @param string $name 字符串
     * @param integer $type 转换类型
     * @param bool $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name = null, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}