<?php


namespace yiqiniu\console\command;


use ReflectionClass;
use think\App;
use think\console\command\Make;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;

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

    // 忽略文件
    protected $ignore_files = [
        'BaseModel', 'PageBase'
    ];
    //
    protected $namespace = 'app';

    // 生成错误的类名
    protected $error_files = [];

    protected function configure()
    {
        parent::configure();
        $this->setName('make:facade')
            ->addArgument('module', Argument::OPTIONAL, "specified Module name")
            ->addArgument('dir', Argument::OPTIONAL, "specified app/module/dir name")
            ->setDescription('Create a new Facade class ');
    }


    protected function execute(Input $input, Output $output)
    {

        $arguments = $input->getArguments();

        $class_name = trim($input->getArgument('name'));
        if (is_null($arguments['module'])) {
            $isdir = false;
        } else {
            $isdir = ($arguments['module'] == 'dir' && is_null($arguments['dir'])) ? true : false;
        }


        $this->app = App::getInstance();

        // 应用路径
        $apppath = $this->app->getAppPath();
        // 类不存在时返回
        if (empty($class_name)) {
            $this->output->writeln('<error>' . $class_name . ': classname not empty.</error>');
            exit;
        }
        // 生成类的列表
        $class_list = [];
        //
        if (!$isdir) {
            if (!class_exists($class_name)) {
                $this->output->writeln('<error>' . $class_name . ': class not exist.</error>');
                exit;
            }
            $class_list[] = $class_name;
            $module_name = trim($input->getArgument('module'));

        } else {
            $class_name = str_replace('/', '\\', $class_name);

            $dirpath = $apppath . $class_name;
            if (!file_exists($dirpath)) {
                $this->output->writeln('<error>' . $class_name . ': dir path not exist.</error>');
                exit;
            }
            // 生成类文件列表
            $files = scandir($dirpath);
            foreach ($files as $file) {
                if ('.' . pathinfo($file, PATHINFO_EXTENSION) === '.php') {
                    $filename = substr($file, 0, -4);
                    if (in_array($filename, $this->ignore_files)) {
                        continue;
                    }
                    $class_list[] = $this->namespace . '\\' . $class_name . '\\' . $filename;
                }

            }
            // 没有找到文件
            if (empty($class_list)) {
                $this->output->writeln('<error>' . $class_name . ': dir path no found files.</error>');
                exit;
            }
            $module_name = dirname($class_name);
        }

        // 获取stub代码
        $stubs = $this->getStub();
        $facade_stub = file_get_contents($stubs['facade']);

        foreach ($class_list as $v) {
            $this->makeFacade($v, $module_name, $facade_stub, $apppath);
        }


        $output->writeln('<info>' . $this->type . ':' . 'Facede Class created successfully.</info>');

    }

    /**
     * 生成代理类
     * @param $class                    类名
     * @param $model                    模块名
     * @param string $apppath 应用程序路径
     * @throws \ReflectionException
     */
    protected function makeFacade($class_name, $module_name, $facade_stub, $apppath = '')
    {

        try {
            // 解析当前类
            $ref = new ReflectionClass($class_name);
            $methods = $ref->getMethods();

            $funs = [];
            //解决类的所有public方法
            foreach ($methods as $method) {
                if ($method->class !== $class_name)
                    continue;
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

                    $funs[$method->name]['comment'] = str_replace([' * ', "\r", "\n", "\r\n"], '', $doc);
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
                            if (empty($param_default)) {
                                $param_default = " ''";
                            } else {
                                $param_default = (empty($param_type) || $param_type == 'string') ? "'$param_default'" : $param_default;
                            }

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
            $apppath = $apppath ?? $this->app->getAppPath();
            if (!empty($module_name)) {
                $dirname = $apppath . $module_name . '\\facade\\';
            } else {
                $dirname = $apppath . 'facade\\';
            }
            if (!file_exists($dirname)) {
                if (!mkdir($dirname, 0644, true) && !is_dir($dirname)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
                }
            }

            // 写入文件
            $model_file = $dirname . $base_class_name . '.php';
            //  直接替换
            file_put_contents($model_file, str_replace(['{%namespace%}', '{%className%}', ' {%methods%}', '{%fullclassname}'], [
                $namespace,
                $base_class_name,
                $method_str,
                $class_name
            ], $facade_stub));


        } catch (\ReflectionException $e) {
            $error_files[$class_name] = $e->getMessage();
        } catch (Exception $e) {
            $error_files[$class_name] = $e->getMessage();
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