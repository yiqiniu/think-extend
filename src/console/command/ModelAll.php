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
class ModelAll extends Make
{

    protected $type = 'Command';

    // 基础Model的名称
    protected $baseModel = 'BaseModel';

    //配置模板
    protected $stubs = [
        'basemodel' => 'basemodel',
        'model' => 'model',
    ];

    protected $app = null;
    // 不能当做类名的表名

    protected $keywords = ['Abstract', 'Class', 'Traits'];

    // 数据库架构名,PGsql 有效
    protected $schema_name = 'public';

    //是否pgsql数据库
    protected $is_postgressql = false;

    protected function configure()
    {
        $this->setName('make:modelall')
            ->addOption('force', '-f', Option::VALUE_NONE, "force update")
            ->addOption('schema', '-s', Option::VALUE_REQUIRED, "specified schema name")
            ->addOption('module', '-m', Option::VALUE_REQUIRED, "specified Module name")
            ->addOption('keyword', '-k', Option::VALUE_REQUIRED, "specified table name keyword")
            ->addOption('subdirectory', '-d', Option::VALUE_REQUIRED, "specified SubDirectories")
            ->setDescription('Generate all models from the database');
    }


    protected function execute(Input $input, Output $output)
    {

        // 关键字
        $keyword = $input->getOption('keyword');
        // 子目录
        $subDir = strtolower($input->getOption('subdirectory'));

        $db_connect = null;

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
        $this->is_postgressql = stripos($connect['type'], 'pgsql') !== false;
        $db_connect = Db::connect($default ?: $connect);
        if ($this->is_postgressql != false) {

            if ($schema = trim($input->getOption('schema'))) {
                $this->schema_name = $schema;
            }

            $tablelist = $db_connect->table('pg_class')
                ->field(['relname as name', "cast(obj_description(relfilenode,'pg_class') as varchar) as comment"])
                ->where('relname', 'in', function ($query) {
                    $query->table('pg_tables')
                        ->where('schemaname', $this->schema_name)
                        ->whereRaw("position('_2' in tablename)=0")->field('tablename');
                })->select();

        } else {


            $tablelist = $db_connect->table('information_schema.tables')
                ->where('table_schema', $connect['database'])
                ->field('table_name as name,table_comment as comment')
                ->select();
        }
        //select table_name,table_comment from information_schema.tables where table_schema='yiqiniu_new';

        // 获取数据库配置
        $name = trim($input->getOption('module'));
        $apppath = $this->app->getAppPath();
        if (!empty($name)) {
            $dirname = $apppath . $name . DIRECTORY_SEPARATOR . 'model';
        } else {
            $dirname = $apppath . 'model';
        }
        $dirname .= DIRECTORY_SEPARATOR;

        // model 保存的目录
        $basemodel_path = $dirname;
        // 保存到子目录中
        if (!empty($subDir)) {
            $dirname .= strtolower($subDir) . DIRECTORY_SEPARATOR;
        }

        if (!file_exists($dirname)) {
            if (!mkdir($dirname, 0644, true) && !is_dir($dirname)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
            }
        }
        $basemodel_namespce = $this->getNamespace2($name, '');
        // 获取生成空间的名称
        $namespace = $this->getNamespace2($name, $subDir);

        // 判断 是否有基本BaseModel


        $stubs = $this->getStub();

        // 写入基本的Model类
        $basemodel_file = $basemodel_path . $this->baseModel . '.php';

        if (!file_exists($basemodel_file)) {
            $basemodel = file_get_contents($stubs['basemodel']);
            file_put_contents($basemodel_file, str_replace(['{%namespace%}', '{%className%}',], [
                $basemodel_namespce,
                $this->baseModel,
            ], $basemodel));
        }


        // 生成所有的类
        $prefix_len = strlen($connect['prefix']);

        $model_stub = file_get_contents($stubs['model']);

        //强制更新
        $force_update = $input->getOption('force');

        //
        $use_content = empty($subDir)?'':'use '.($basemodel_namespce.'\\'.$this->baseModel.';');
        foreach ($tablelist as $k => $table) {
            // 处理关键字
            if (!empty($keyword) && stripos($table['name'], $keyword) === false) {
                continue;
            }
            $class_name = $this->parseName(substr($table['name'], $prefix_len), 1, true);
            // 如果是表名是class的改为ClassModel

            /*$tablename = '';
            if (in_array($class_name, $this->keywords)) {
                $class_name .= 'Model';
                $tablename = "protected \$name='" . substr($table['name'], $prefix_len) . "';";
            }*/

            $field = $this->getTablesField($db_connect,$table['name']);
            $tablename = substr($table['name'], $prefix_len);

            $model_file = $dirname . $class_name . 'Model.php';
            if (!file_exists($model_file) || $force_update) {
                file_put_contents($model_file, str_replace(['{%namespace%}', '{%use%}','{%className%}', '{%comment%}', '{%tablename%}','{%fields%}'], [
                    $namespace,
                    $use_content,
                    $class_name,
                    $table['comment'],
                    $tablename,
                    $field
                ], $model_stub));
            }

        }


        $output->writeln('<info>' . $this->type . ':' . 'All Table Model created successfully.</info>');


    }

    /**
     * 生成目录类的命令空间
     * @param $app
     * @param string $subdir
     * @return string
     */
    protected function getNamespace2($app, $subdir = '')
    {
        return 'app' . (empty($app) ? '' : ('\\' . $app)) . '\\model' . (empty($subdir) ? '' : '\\' . $subdir);
    }


    /**
     * 获取生成的代码模板
     * @return string[]
     */
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

    /**
     * 获取表的字段
     */
    public function getTablesField($db, $tablename)
    {

        if ($this->is_postgressql) {
            $sql = "SELECT
            a.attname as field,
            format_type(a.atttypid,a.atttypmod) as type,
            col_description(a.attrelid,a.attnum) as comment
            FROM pg_class as c,pg_attribute as a
            where c.relname = '$tablename' and a.attrelid = c.oid and a.attnum>0;";

        } else {

            $sql = 'SHOW FULL COLUMNS FROM	' . $tablename;

        }
        $fields = $db->query($sql);

        $format =  "'%s'=>'%s',\t\t// %s";

        $retdata=[];
        //生成字段
        foreach ($fields as $field) {
            $field['field'] = $field['Field'] ?? $field['field'];
            $field['type'] = $field['Type'] ?? $field['type'];
            $field['notnull'] = $field['Null'] ?? $field['notnull'];
            $field_type = 'string';

            //字数类型
            if(stripos($field['type'],'int')!==false){
                $field_type='int';
            }
            //日期
            if(stripos($field['type'],'time')!==false || stripos($field['type'],'date')!==false){
                $field_type='datetime';
            }
            //浮点
            if(stripos($field['type'],'float')!==false || stripos($field['type'],'numeric')!==false ||
                stripos($field['type'],'decimal')!==false){
                $field_type='float';
            }
            //json 格式
            if(stripos($field['type'],'json')!==false){
                $field_type='json';
            }
            $retdata[]=sprintf($format,$field['field'],$field_type,$field['comment']);
        }
        return implode("\r\n\t\t",$retdata);
    }

}