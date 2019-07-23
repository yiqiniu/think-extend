<?php

use think\exception\HttpResponseException;
use yiqiniu\facade\Logger;

if (!function_exists('api_exception')) {
    /**
     * @param int $code 异常代码
     * @param string $msg 异常信息
     * @throws \yiqiniu\exception\ApiException
     */
    function api_exception($code, $msg)
    {
        if (!is_numeric($code) && !empty($msg)) {
            $msg2 = $code;
            $code = $msg;
            $msg = $msg2;
        }
        if (!is_numeric($code)) {
            $msg = $code;
            $code = API_VAILD_EXCEPTION;
        }
        throw  new yiqiniu\exception\ApiException($msg, $code);
    }
}


if (!function_exists('api_result')) {
    /** 输出返回结果
     * @param $code
     * @param string $msg
     * @param array $data
     * @throws HttpResponseException
     */
    function api_result($code, $msg = '', $data = [])
    {

        if ($code instanceof yiqiniu\exception\ApiException) {
            $data = $code->getData();
            if (isset($data['data']))
                $data = $data['data'];
            $msg = $code->getMessage();
            $code = $code->getCode();
        } elseif ($code instanceof think\Exception) {
            // 记录异常
            Logger::exception($code);
            $msg = $code->getMessage();
            $code = $code->getCode();
        } elseif ($code instanceof \think\exception\ValidateException) {
            // 验证异常
            $msg = $code->getMessage();
            $code = API_VAILD_EXCEPTION;
        } elseif (is_object($code)) {
            $data = $code->toArray();
            $code = API_SUCCESS;
        } else if (is_array($code)) {
            $data = $code;
            $code = API_SUCCESS;
        } else if (is_string($code)) {
            $msg = $code;
            $code = API_SUCCESS;
        } else {
            $code = API_SUCCESS;
        }
        $result = [
            'code' => $code,
            'msg' => $msg != '' ? $msg : (config('status.')[$code]),
            'time' => time(),
            'data' => $data
        ];
        $response = \think\Response::create($result, 'json');
        throw new HttpResponseException($response);
    }
}


if (!function_exists('httpRequest')) {
    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param string $method 请求方法GET/POST
     * @param bool $upload
     * @param array $header
     * @return array  $data   响应数据
     * @throws \Exception
     */
    function httpRequest($url, $params = [], $method = 'GET', $upload = false, $header = [])
    {
        try {
            $method = strtoupper($method);
            $opts = [
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_AUTOREFERER => true,
                CURLOPT_FOLLOWLOCATION => 1
            ];
            /* 根据请求类型设置特定参数 */
            switch (strtoupper($method)) {
                case 'GET':
                    if (!empty($params)) {
                        $params = is_array($params) ? http_build_query($params) : $params;
                        $url = $url . (strpos($url, "?") > 0 ? "&" : "?") . $params;
                    }
                    $opts[CURLOPT_URL] = $url;
                    break;
                case 'POST':
                    $opts[CURLOPT_URL] = $url;
                    $opts[CURLOPT_POST] = 1;
                    //判断是否传输文件
                    if ($upload) {        //设置上传文件
                        $file = new \CURLFile($upload['file'], $upload['type'], $upload['name']);
                        $params[] = $file;
                    }
                    $opts[CURLOPT_POSTFIELDS] = $params;
                    break;
                default:
                    api_exception(API_VAILD_EXCEPTION, '不支持的请求方式！');
            }
            /* 初始化并执行curl请求 */
            $ch = curl_init();
            curl_setopt_array($ch, $opts);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error)
                api_exception(API_VAILD_EXCEPTION, '请求发生错误：' . $error);
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

if (!function_exists('writelog')) {
    /**
     * 写入日志
     * @param  $content  string  要写入的日志
     * @append  boole  追加 true   false 不追加
     *
     *
     */
    function writelog($content, $append = true)
    {
        Logger::log($content, $append);
    }

}
