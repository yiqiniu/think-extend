<?php
/**
 * Created by PhpStorm.
 * User: gjianbo
 * Date: 2018/12/24
 * Time: 17:50
 */

namespace yiqiniu\extend\library;


class Logger
{


    private $config = [
        //自动删除
        'auto_delete' => false,
        //保留天数
        'reserve_days' => 7,
        //日志格式
        'format' => 'array',
        //保存位置
        'save_path' => ''
    ];

    /**
     * 架构函数
     * @param array $config 连接配置
     * @access public
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['save_path'])) {
            $this->config['save_path'] = $this->getRuntimePath();
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
        $logdata['request_uri'] = $_SERVER['REQUEST_URI'] ?? '';

        $controller = request()->controller(true);
        $logdata['post'] = $_POST;
        $logdata['get'] = $_GET;
        $logdata['message'] = $e->getMessage();
        $logdata['file'] = $e->getFile();
        $logdata['line'] = $e->getLine();
        $logdata['trace'] = $e->getTraceAsString();


        $exception_log = $this->config['save_path'] . '/exception/' . date('Ym') . '/' . ($controller === '' ? '' : $controller . '_') . date('Ymd') . '.log';

        $this->writeLogger($exception_log, print_r($logdata, true));
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
            file_exists($dirname) || mkdir($dirname, 0755, true) || is_dir($dirname);

            if (!is_string($strdata)) {
                if ($this->config['format'] === 'json') {
                    $strdata = json_encode($strdata, JSON_UNESCAPED_UNICODE);
                } else {
                    $strdata = print_r($strdata, true);
                }
            }
            $str = "[" . date("Y-m-d H:i:s") . "]" . $strdata . "\r\n";
            if ($append)
                $rs = fopen($filename, 'a+');
            else {
                $rs = fopen($filename, 'w');
            }
            fwrite($rs, $str);
            fclose($rs);
            // 删除历史日志
            $this->deleteHistroy($filename);
            return true;
        } catch (\Exception $e) {

            return false;
        }

    }

    /**
     *  记录日志到文件中
     * @param        $content string|array|object  要记录的内容
     * @param        $append  bool|string     内容是否追加
     * @param        $prefix  string   文件名前缀
     * @param string $dir
     * @param string $format
     * @return bool
     */
    public function log($content, $append = true, $prefix = '', $dir = 'logs', $format = 'array')
    {

        if (is_string($append)) {
            if (!empty($prefix)) {
                $format = $dir;
                $dir = $prefix;
            }
            $prefix = $append;
            $append = true;
        }
        // 保存格式
        $this->config['format'] = $format;
        $dir = empty($dir) ? 'logs' : $dir;
        $logfile = $this->config['save_path'] . '/' . $dir . '/' . date('Ym') . '/' . ($prefix !== '' ? $prefix . '_' : '') . date('Ymd') . '.log';
        return $this->writeLogger($logfile, $content, $append);
    }

    /**
     * 删除历史
     * @param $filename      string 当前日期的文件名
     */
    private function deleteHistroy($filename)
    {
        if ($this->config['auto_delete']) {
            $del_date = date('Ymd', strtotime('-' . ($this->config['reserve_days']) ?? '7') . 'day');
            $filename = str_replace(date('Ymd'), $del_date, $filename);
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * 获取当有框架的runtime目录
     */
    private function getRuntimePath()
    {
        if (defined("RUNTIME_PATH")) {
            $runtime_path = RUNTIME_PATH;
        } elseif (function_exists("app")) {
            $runtime_path = app()->getRuntimePath();
        } else {
            $runtime_path = __DIR__ . '/runtime';
        }
        if (!file_exists($runtime_path)) {
            if (!mkdir($concurrentDirectory = $runtime_path, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        return $runtime_path;
    }
}
