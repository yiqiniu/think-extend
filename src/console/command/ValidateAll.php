<?php


namespace yiqiniu\console\command;


use think\App;
use think\console\command\Make;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use yiqiniu\facade\Db;

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

    protected function configure()
    {
        $this->setName('make:validateall')
            ->addArgument('all', Argument::OPTIONAL, "Make All Fields")
            ->addArgument('model', Argument::OPTIONAL, "specified Module name")
            ->setDescription('make database  all table  ');
    }


    protected function execute(Input $input, Output $output)
    {

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
        $tablelist = Db::connect($default ?: $connect)->table('information_schema.tables')
            ->where('table_schema', $connect['database'])
            ->field('table_name as name,table_comment as comment')
            ->select();
        //select table_name,table_comment from information_schema.tables where table_schema='yiqiniu_new';

        // 全部
        $this->allfield = empty($input->getArgument('all')) ? false : true;

         // 获取数据库配置
        $name = trim($input->getArgument('model'));
        $apppath = $this->app->getAppPath();
        if (!empty($name)) {
            $dirname = $apppath . $name . '\\validate\\';
        } else {
            $dirname = $apppath . 'validate\\';
        }
        if (!file_exists($dirname)) {
            mkdir($dirname, 0644, true);
        }
        // 获取生成空间的名称
        $namespace = $this->getNamespace2($name);

        // 判断 是否有基本BaseModel

        $stubs = $this->getStub();


        // 生成所有的类
        $prefix_len = strlen($connect['prefix']);

        $model_stub = file_get_contents($stubs['validate']);

        // table 类用于获取字段
        $dbs = Db::connect($default ?: $connect)->getConnection();

        foreach ($tablelist as $k => $table) {
            $class_name = $this->parseName(substr($table['name'], $prefix_len), 1, true);
            // 如果是表名是class的改为ClassModel
            $filedinfo = $this->getTablesField($dbs, $table['name']);
            $model_file = $dirname . $class_name . 'Valid.php';
            if (!file_exists($model_file)) {
                file_put_contents($model_file, str_replace(['{%namespace%}', '{%className%}', '{%comment%}', '{%rule%}', '{%message%}'], [
                    $namespace,
                    $class_name,
                    $table['comment'],
                    $filedinfo['rule'],
                    $filedinfo['message'],
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
        $fields = $db->getFields($tablename);
        // 生成模板
        $templates = [
            'rule' => "'%s'=>'require',\r\n\t\t",
            'message' => "'%s.require'=>'%s不能为空',\r\n\t\t",
        ];
        //返回值
        $retdata = [
            'rule' => '',
            'message' => ''
        ];
        //忽略ID
        $ignorefield = ['id', 'bz', 'memo', 'createdate', 'createtime', 'remark', 'status', 'zt'];
        //生成枯
        foreach ($fields as $field => $data) {

            if ($this->allfield) {
                if (in_array($field, $ignorefield))
                    continue;
            } else {

                if ($data['notnull'] == false)
                    continue;
            }
            $retdata['rule'] .= sprintf($templates['rule'], $field);
            $retdata['message'] .= sprintf($templates['message'], $field, isset($data['comment']) ? $data['comment'] : $field);
        }
        return $retdata;
    }

    protected function getNamespace2($model)
    {


        return empty($model) ? 'app\\validate' : 'app\\' . $model . '\\validate';
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