<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://library.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace yiqiniu\library;

use CURLFile;
use think\Exception;

/**
 * CURL数据请求管理器
 * Class Http
 * @package library\tools
 */
class Http
{

    /**
     * 以get模拟网络请求
     * @param string $url HTTP请求URL地址
     * @param array $query GET请求参数
     * @param array $options CURL参数
     * @return boolean|string
     */
    public static function get($url, $query = [], $options = [])
    {
        $options['query'] = $query;
        return self::request('get', $url, $options);
    }

    /**
     * 以post模拟网络请求
     * @param string $url HTTP请求URL地址
     * @param array $data POST请求数据
     * @param array $options CURL参数
     * @return boolean|string
     */
    public static function post($url, $data = [], $options = [])
    {
        $options['data'] = $data;
        return self::request('post', $url, $options);
    }

    /**
     * 以request payload数据进行提交
     * @param $url
     * @param array $data
     * @return bool|string
     */
    public static function payload($url, $data = [])
    {
        $options['headers'] = ['Content-Type: application/json'];
        $options['data'] = $data;
        return self::request('post', $url, $options, true);
    }


    /**
     * CURL模拟网络请求
     * @param string $method 请求方法
     * @param string $url 请求方法
     * @param array $options 请求参数[headers,data]
     * @param bool $pyload
     * @return boolean|string
     */
    public static function request($method, $url, $options = [], $pyload = false)
    {
        $curl = curl_init();
        if ($method === 'get' && $pyload = true) {
            $pyload = false;
        }
        // GET 参数设置
        if (!empty($options['query'])) {
            $url .= (stripos($url, '?') !== false ? '&' : '?') . http_build_query($options['query']);
        }
        // 浏览器代理设置
        curl_setopt($curl, CURLOPT_USERAGENT, self::getUserAgent());
        // CURL 头信息设置
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        // Cookie 信息设置
        if (!empty($options['cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $options['cookie']);
        }
        if (!empty($options['cookie_file'])) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $options['cookie_file']);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $options['cookie_file']);
        }
        // POST 数据设置
        if (strtolower($method) === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($pyload) {
                $postdata = is_array($options['data']) ? json_encode($options['data']) : $options['data'];
            } else {
                $postdata = self::buildQueryData($options['data']);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        }
        // 请求超时设置
        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        } else {
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($curl);
        // 记录错误信息
        $error = null;
        if ($content === false) {
            $error = [
                'code' => curl_errno($curl),
                'msg' => curl_error($curl),
            ];
        }
        curl_close($curl);
        if ($error) {
            Logger::log(
                [
                    'url' => $url,
                    'method' => $method,
                    'options' => $options,
                    'error' => $error
                ], true, 'curl_error', 'error');
        }
        return $content;
    }

    /**
     * POST数据过滤处理
     * @param array $data 需要处理的数据
     * @param boolean $build 是否编译数据
     * @return array|string
     */
    private static function buildQueryData($data, $build = true)
    {
        if (!is_array($data))
            return $data;
        foreach ($data as $key => $value) {
            if (is_object($value) && $value instanceof \CURLFile) {
                $build = false;
            } elseif (is_string($value) && class_exists('CURLFile', false) && stripos($value, '@') === 0) {
                if (($filename = realpath(trim($value, '@'))) && file_exists($filename)) {
                    list($build, $data[$key]) = [false, new \CURLFile($filename)];
                }
            }
        }
        return $build ? http_build_query($data) : $data;
    }


    /**
     * 获取浏览器代理信息
     * @return string
     */
    private static function getUserAgent()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) return $_SERVER['HTTP_USER_AGENT'];
        $userAgents = [
            'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.3; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
        ];
        return $userAgents[array_rand($userAgents, 1)];
    }


    /**
     * 根据文件路径获取一个CURLFile类实例
     * @param string $file 文件路径
     * @param string $mime
     * @param string $filename
     * @return CURLFile
     * @Date 2019/4/29
     */
    public static function makeCurlFile(string $file, string $mime = '', string $filename = '')
    {

        /**
         * .xls mime为 application/vnd.ms-excel
         * .xlsx mime为 application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
         * 可参考 https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types
         *
         *  注意：也可以使用 finfo类动态获取，但需要装fileinfo扩展
         *  demo:
         * $result = new finfo();
         * if (is_resource($result) === true) {
         * return $result->file($filename, FILEINFO_MIME_TYPE);
         * }
         * return false;
         */
        if (empty($mime)) {
            $mime = 'application/octet-stream';
            if (file_exists('mime_content_type')) {
                $mime = mime_content_type($file);
            }
        }
        if (empty($filename)) {
            $info = pathinfo($file);
            $filename = $info['basename'];
        }
        return new CURLFile($file, $mime, $filename);
    }


    /**
     * 异步GET请求
     * @param $url
     * @param array $query
     * @param array $options
     * @param null $callback
     * @return array
     * @throws Exception
     */
    public static function async_get($url, $query = [], $options = [])
    {
        $fsockOpt = self::async_option($options);

        if (!empty($query)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($query);
        }
        list($fp, $path, $purl) = self::async_NewChannel($url, $fsockOpt);
        if (empty($fp)) {
            return false;
        }
        $header = 'GET ' . $path . ' ' . $fsockOpt['HttpVersion'] . "\r\n";
        $header .= 'Host: ' . $purl['host'] . "\r\n";
        $header .= 'Connection: ' . $fsockOpt['Connection'] . "\r\n"; //持久连接
        $header .= 'User-Agent: ' . $fsockOpt['UserAgent'] . "\r\n"; //浏览器
        $header .= 'Accept: */*\r\n';
        return self::async_Request($fp, $header, $fsockOpt);
    }

    /**
     * 异步POST请求
     * @param $url
     * @param $data
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public static function async_post($url, $data, $options = [])
    {
        $fsockOpt = self::async_option($options);

        if (is_string($data)) {
            $Content = $data;
        } else {
            $Content = http_build_query($data);
        }
        list($fp, $path, $purl) = self::async_NewChannel($url, $fsockOpt);
        if (empty($fp)) {
            return false;
        }
        $header = 'POST ' . $path . ' ' . $fsockOpt['HttpVersion'] . "\r\n";
        $header .= 'Host: ' . $purl['host'] . "\r\n";
        $header .= 'Connection: ' . $fsockOpt['Connection'] . "\r\n"; //持久连接
        $header .= 'Content-Length: ' . strlen($Content) . "\r\n";
        $header .= 'Origin: ' . $purl['scheme'] . '://' . $purl['host'] . "\r\n";
        // $header .= "X-Requested-With: XMLHttpRequest\r\n"; //AJax 异步请求
        $header .= 'User-Agent: ' . $fsockOpt['UserAgent'] . "\r\n"; //浏览器
        $header .= 'Content-Type: ' . $fsockOpt['Content_Type'] . "\r\n"; //提交方式
        $header .= "Accept: */*\r\n";


        $result= self::async_Request($fp, $header . $Content, $fsockOpt);
        return $result['body'];
    }

    /**
     * 异步文件上传
     * @param $url
     * @param $files
     * @param array $options
     * @return array
     * @throws Exception
     */
    public static function async_upload($url, $files, $options = [])
    {


        $file_array = is_array($files) ? $files : [$files];
        mt_srand((double)microtime() * 1000000);
        $boundary = '----WebKitFormBoundary' . substr(md5(random_int(0, 32000)), 8, 16); //WebKit

        $data = '--' . $boundary;
        foreach ($file_array as $i => $iValue) {


            if (file_exists('mime_content_type')) {
                $content_type = mime_content_type($iValue);
            } else {
                $fileType = pathinfo($iValue, PATHINFO_EXTENSION | PATHINFO_FILENAME);
                switch ($fileType) {
                    case 'gif':
                        $content_type = 'image/gif';
                        break;
                    case 'png':
                        $content_type = 'image/png';
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $content_type = 'image/jpg';
                        break;
                    default:
                        $content_type = 'application/octet-stream';
                        break;
                }
            }

            $content_file = implode('', file($iValue));
            $data .= "\r\nContent-Disposition: form-data; name=\"file" . ($i + 1) . '"; filename="' . basename($iValue) . '"';
            $data .= "\r\nContent-Type: " . $content_type;
            $data .= "\r\n\r\n" . $content_file . "\r\n--" . $boundary;
        }
        $data .= "--\r\n\r\n";
        $options['Content_Type'] = 'multipart/form-data; boundary=' . $boundary;
        return self::async_post($url, $data, $options);

    }

    /**
     * 异步发送json数据
     * @param $url
     * @param $data
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public static function async_payload($url, $data, $options = [])
    {
        $options['Content_Type'] = 'application/json';
        $postdata = is_array($data) ? json_encode($data) : $data;
        return self::async_post($url, $postdata, $options);

    }

    /**
     * @param $header
     * @param $fp
     * @param $fsockOpt
     * @return array
     */
    private static function async_Request($fp, $header, $fsockOpt)
    {

        if ($fsockOpt['OnContinue'] === false) { //防止返回 HTTP/1.1 100 Continue
            $header .= "Expect:\r\n";
        }
        if (!empty($options['Referer'])) {
            $header .= 'Referer: ' . $options['Referer'] . "\r\n"; //来源
        }
        if (!empty($fsockOpt['AcceptEncoding'])) {
            $header .= 'Accept-Encoding: ' . $fsockOpt['AcceptEncoding'] . "\r\n"; //压缩编码
        }
        $header .= 'Accept-Language: ' . $fsockOpt['AcceptLanguage'] . "\r\n"; //语言
        if (!empty($fsockOpt['Cookie'])) {
            $header .= 'Cookie: ' . $fsockOpt['Cookie'] . "\r\n";
        }
        $header .= "\r\n";
        return self::async_FWriteOut($fp, $header, $fsockOpt);

    }


    /**
     * @param $fp
     * @param $data
     * @param $options
     * @return array
     */
    private static function async_FWriteOut($fp, $data, $options)
    {
        $result = '';
        fwrite($fp, $data);
        $length = 4096;
        while (!feof($fp)) {
            $result .= fgets($fp, $length);
        }
        fclose($fp);

        list($RetHeader, $RetBody) = explode("\r\n\r\n", $result, 2);
        if ($options['OnContinue'] && preg_match('/^HTTP\/[1|2]\.[0|1]\s100\sContinue/i', $RetHeader)) { //处理 HTTP/1.1 100 Continue 数据
            list($RetHeader, $RetBody) = explode("\r\n\r\n", $RetBody, 2);
        }
        if (!preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $RetHeader, $StatusCode)) { //处理状态码
            $StatusCode = array(0, 0);
        }
        if (preg_match('/Transfer-Encoding:\s?chunked/i', $RetHeader)) { //检测是否使用 分块字段
            $RetBody = self::async_DecodeChunked($RetBody);
        }
        return ['code' => $StatusCode[1], 'header' => $RetHeader, 'body' => trim($RetBody)];
    }

    /**
     * 产生异步请求的配置
     * @param array $options
     * @return array
     * @throws Exception
     */
    private static function async_option($options = [])
    {

        if (!function_exists('fsockopen')) {
            throw new Exception('php This function is not supported fsockopen');
        }

        $def_option = [
            //阻塞模式
            'stream' => false,
            //连接/运行时间
            'timeout' => 3,
            //连接协议 tcp
            'xport' => 'tcp',
            //浏览器
            'UserAgent' => self::getUserAgent(),
            //压缩编码 gzip, deflate, sdch
            'AcceptEncoding' => '',
            //语言
            'AcceptLanguage' => 'zh-CN,zh;q=0.8',
            //持久连接 keep-alive 关闭 Close
            'Connection' => 'Close',
            //使用HTTP 1.0协议，服务器会主动放弃chunked编码
            'HttpVersion' => 'HTTP/1.1',
            // 关闭 HTTP/1.1 100 Continue 返回 可能造成 HTTP/1.1 417 Expectation Failed
            'OnContinue' => false,
            //提交表单方式
            'Content_Type' => 'application/x-www-form-urlencoded;charset=utf-8',
            //提交表单方式
            'Accept' => 'application/x-www-form-urlencoded;charset=utf-8',
        ];
        $option = array_merge($def_option, $options);

        if (in_array($option['xport'], stream_get_transports()) == false) {
            throw new Exception('Server does not support (' . $option['xport'] . ') Transfer Protocol!');
        }

        return $option;
    }

    /**
     *
     * @param $url
     * @return array
     * @throws Exception
     */
    private static function async_NewChannel($url, $option = [])
    {

        $purl = [];
        try {
            $purl = parse_url($url);
            if ($purl === false) {
                throw  new \Exception('parse_url error');
            }
            $errno = 0;
            $errstr = '';
            // $purl = parse_url($url);
            if ($option['xport'] !== 'tcp') {
                $xport = $option['xport'] . '://';
                if (isset($purl['host'])) {
                    $host = $purl['host'];
                } else {
                    $host = $purl['path'];
                }
            } else {
                $xport = ($purl['scheme'] == 'https') ? 'ssl://' : ''; // tls://
                $host = $purl['host'];
            }
            if (!isset($purl['port']) || empty($purl['port'])) {
                if ($purl['scheme'] == 'https') {
                    $port = 443;
                } else {
                    $port = 80;
                }
            } else {
                $port = $purl['port'];
            }
            // $port 使用-1表示不使用端口,例如 unix:// 资源
            // $timeout  连接时间
            $fp = fsockopen($xport . $host, $port, $errno, $errstr, $option['timeout']);
            if (false === $fp) {
                throw new Exception('Connection error:(' . $errno . ') ' . $errstr);
            }
            //关闭阻塞模式
            if ($option['stream'] === false && !stream_set_blocking($fp, 0)) {
                throw new Exception('ERROR：Failed to close blocking mode!');
            }
            $query = isset($purl['query']) ? '?' . $purl['query'] : '';
            $path = $purl['path'] ?? '/';
            return [$fp, $path . $query, $purl];
        } catch (\Exception $e) {
            return [null, '', $purl];
        }
    }


    /**
     * @param $Body
     * @return string
     */
    private static function async_DecodeChunked($Body)
    {
        $ret = '';
        $i = $chunk_size = 1;
        while ($chunk_size > 0 && $i < 100) { //最多100个分块防止死循环
            $footer_position = strpos($Body, "\r\n");//查找一个footer位置
            $chunk_size = (integer)hexdec(substr($Body, 0, $footer_position));//十六转十进制
            $NewBody = substr($Body, $footer_position + 2); //全部(去除footer标识部分)
            $ret .= substr($NewBody, 0, $chunk_size); //本次
            $Body = substr($NewBody, $chunk_size + 2); //剩余
            ++$i;
        }
        return $ret;
    }


}
