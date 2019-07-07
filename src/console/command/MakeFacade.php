<?php


namespace yiqiniu\console\command;


use ReflectionClass;
use think\console\command\Make;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * Class ModelAll
 * @package yiqiniu\console\command
 */
class MakeFacade extends Make
{

    protected $type = 'Command';


    protected $app = null;
    // 不能当做类名的表名

    protected $stubs = [
        'facade' => 'facade',
    ];

    // 是否全部字段 , false 为不为空的字段,true 全部字段
    protected $allfield = false;

    protected function configure()
    {
        parent::configure();
        $this->setName('make:facade')
            ->addArgument('module', Argument::OPTIONAL, "specified Module name")
            ->setDescription('Create a new Facade class ');
    }


    protected function execute(Input $input, Output $output)
    {

        $class_name = trim($input->getArgument('name'));

        // 类不存在时返回
        if (!class_exists($class_name)) {
            $this->output->writeln('<error>' . $class_name . ': class not exists.</error>');
            exit;
        }

        $module_name = trim($input->getArgument('module'));

        try {
            // 解析当前类
            $ref = new ReflectionClass($class_name);
            $methods = $ref->getMethods();
            $funs = [];
            //解决类的所有public方法
            foreach ($methods as $method) {
                // 排除特殊的方法
                if (substr($method->name, 0, 2) == '__')
                    continue;
                if ($method->isPublic()) {
                    // 获取注释内容
                    $doccomment = $method->getDocComment();
                    $doccomment = str_replace("\r\n", "\n", $doccomment);
                    if (strpos($doccomment, "\n") !== false) {
                        $doc = explode("\n", $method->getDocComment())[1];
                    } else {
                        $doc = $method->getDocComment();
                    }
                    $funs[$method->name]['comment'] = str_replace(' * ', '', $doc);
                    //函数名称
                    $funs[$method->name]['name'] = $method->getName();
                    // 返回值
                    $returnType = $method->getReturnType();
                    $funs[$method->name]['return'] = empty($returnType) ? 'mixed' : $returnType->getName();
                    // 参数
                    $parameters = $method->getParameters();
                    $parameter_str = '';
                    $usedefault = false;
                    foreach ($parameters as $k => $param) {
                        $param_name = $param->name;
                        $type = $param->getType();
                        $param_type = empty($type) ? '' : $type->getName();
                        $param_default = '';
                        // 参数模板值
                        if ($param->isOptional()) {
                            $param_default = $param->getDefaultValue();
                            if ($param_type == 'bool' && !empty($param_default)) {
                                $param_default = $param_default ? 'true' : 'false';
                            }
                        }

                        if (empty($param_default) && $usedefault == false) {
                            $parameter_str .= $param_type . ' $' . $param_name . ',';
                        } else {
                            $param_default = empty($param_default) ? " ''" : $param_default;
                            $parameter_str .= $param_type . ' $' . $param_name . ' = ' . $param_default . ',';
                            $usedefault = true;
                        }

                    }
                    $funs[$method->name]['args'] = substr($parameter_str, 0, -1);
                }
            }

            // 方法注解的格式
            $method_format = " * @method %s %s(%s) static %s \r\n";
            $method_str = '';
            foreach ($funs as $fun) {
                $method_str .= sprintf($method_format, $fun['return'], $fun['name'], $fun['args'], $fun['comment']);
            }

            // 获取生成空间的名称
            $namespace = $this->getNamespace2($module_name);
            // 获取基本的类名
            $base_class_name = $this->classBaseName($class_name);
            // 判断目录是否存在
            $apppath = $this->app->getAppPath();
            if (!empty($module_name)) {
                $dirname = $apppath . $module_name . '\\facade\\';
            } else {
                $dirname = $apppath . 'facade\\';
            }
            if (!file_exists($dirname)) {
                mkdir($dirname, 0644, true);
            }
            // 获取stub代码
            $stubs = $this->getStub();

            $facade_stub = file_get_contents($stubs['facade']);
            // 写入文件
            $model_file = $dirname . $base_class_name . '.php';
            if (!file_exists($model_file)) {
                file_put_contents($model_file, str_replace(['{%namespace%}', '{%className%}', ' {%methods%}', '{%fullclassname}'], [
                    $namespace,
                    $base_class_name,
                    $method_str,
                    $class_name
                ], $facade_stub));
            }

            $output->writeln('<info>' . $this->type . ':' . 'Facede Class created successfully.</info>');

        } catch (\ReflectionException $e) {
            throw  $e;

        }


    }


    protected function getNamespace2($model)
    {
        return empty($model) ? 'app\\facade' : 'app\\' . $model . '\\facade';
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

    private function classBaseName($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

}