<?php
/**
 * Created by PhpStorm.
 * User: gjianbo
 * Date: 2018/12/24
 * Time: 17:50
 */

namespace yiqiniu\library;


class Logger
{

    private $runtime_path = '';

    /**
     * 架构函数
     * @param array $config 连接配置
     * @access public
     */
    public function __construct(array $config = [])
    {
        if (defined("RUNTIME_PATH")) {
            $this->runtime_path = RUNTIME_PATH;
        } elseif (function_exists("app")) {
            $this->runtime_path = app()->getRuntimePath();
        } else {
            $this->runtime_path = dirname(__FILE__) . '/runtime';
            if (!file_exists($this->runtime_path)) {
                mkdir($this->runtime_path, 0777, true);
            }
        }

    }


    /**
     * 记录异常的bug
     * @param mixed $e
     * @return \think\Response|\think\response\Json
     */
    public function exception($e): bool
    {
        if (!$e instanceof \Exception) {
            return false;
        }


        if ($e instanceof HttpException && $e->getStatusCode() == 404) {
            $logdata['code'] = $e->getStatusCode();
        } else {
            $logdata['code'] = $e->getCode();
        }
        $logdata['request_uri'] = $_SERVER['REQUEST_URI']??'';
        $logdata['post'] = $_POST;
        $logdata['get'] = $_GET;
        $logdata['message'] = $e->getMessage();
        $logdata['file'] = $e->getFile();
        $logdata['line'] = $e->getLine();
        $logdata['trace'] = $e->getTraceAsString();


        $exception_log = $this->runtime_path . '/exception/' . date('Ym') . '/' . date('Ymd') . '.log';


        $this->writeLogger($exception_log, $logdata, true);
        return true;

    }

    /**
     * 把内容写入到日志中
     * @param $filename string 要写入文件名
     * @param $strdata string/array 要写入的数据 数组或对象与print_r转换为字符串
     * @return bool   true 保存成功,  false 保存失败
     */

    public function writeLogger($filename, $strdata, $append = true)
    {
        try {
            $dirname = dirname($filename);
            file_exists($dirname) || mkdir($dirname, 0755, true);

            if (!is_string($strdata)) {
                $strdata = print_r($strdata, true);
            }
            $str = "[" . date("Y-m-d H:i:s") . "]" . $strdata . "\r\n";
            if ($append)
                $rs = fopen($filename, "a+");
            else {
                $rs = fopen($filename, "w");
            }
            fwrite($rs, $str);
            fclose($rs);
            return true;
        } catch (\Exception $e) {

            return false;
        }

    }

    /**
     *  记录日志到文件中
     * @param $content  string   要记录的内容
     */
    public function log($content, $append = true)
    {
        $logfile = $this->runtime_path . '/logs/' . date('Ym') . '/' . date('Ymd') . '.log';

        $this->writeLogger($logfile, $content, $append);

    }

}