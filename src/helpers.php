<?php

use think\db\exception\DbException;
use think\exception\HttpResponseException;
use yiqiniu\facade\Logger;
use yiqiniu\facade\Token;
use yiqiniu\library\Http;

if (!function_exists('api_exception')) {
    /**
     * @param int    $code 异常代码
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
     * @param        $code
     * @param string $msg
     * @param array  $data
     * @throws HttpResponseException
     */
    function api_result($code, $msg = '', $data = [])
    {

        if ($code instanceof Exception) {
            if (method_exists($code, 'getData')) {
                $data = $code->getData();
                if (isset($data['data'])) {
                    $data = $data['data'];
                }
                $code = $code->getCode();
            } else {
                Logger::exception($code);
                $code = API_ERROR;
            }
            $msg = $code->getMessage();
        } elseif (is_object($code)) {
            if (method_exists($code, 'toArray')) {
                $data = $code->toArray();
            }
            $code = API_SUCCESS;
        } else if (is_array($code)) {
            $data = $code;
            $code = API_SUCCESS;
        } else if (empty($msg) && is_string($code)) {
            $msg = $code;
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


if (!function_exists('api_refresh_token')) {
    /**
     * 刷新token
     */
    function api_refresh_token()
    {
        //获取用户的认证信息
        $header = request()->header();
        $refresh_token = request()->post('refresh_token', '');
        if (!empty($refresh_token)) {
            $tokenBody = Token::verifyToken($refresh_token, $header['app'] ?? '');
            $token = Token::getToken($tokenBody);
            api_result($token);
        } else {
            api_result(API_VAILD_EXCEPTION, 'refresh_token 不能为空');
        }
    }
}


if (!function_exists('httpRequest')) {
    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param string $url 请求URL
     * @param array  $params 请求参数
     * @param string $method 请求方法GET/POST
     * @param bool   $upload
     * @param array  $header
     * @return array  $data   响应数据
     * @throws \Exception
     * @throws \yiqiniu\exception\ApiException
     */
    function httpRequest($url, $params = [], $method = 'GET', $upload = false, $header = [])
    {

        $ret = [];
        switch (strtoupper($method)) {
            case 'GET':
                $ret = Http::get($url, $params, $header);
                break;
            case 'POST':
                //设置上传文件
                if (!empty($upload)) {
                    // 上传文件
                    if (is_array($upload)) {
                        $params[] = Http::makeCurlFile($upload['file'], $upload['type'], $upload['name']);
                    }
                    if (is_string($upload)) {
                        $params[] = Http::makeCurlFile($upload);
                    }
                }
                $ret = Http::post($url, $params, $header);
                break;
            case 'PAYLOAD':
                $ret = Http::payload($url, $params);
                break;
            default:
                throw  new yiqiniu\exception\ApiException('CURL不支持的请求方式！', API_VAILD_EXCEPTION);
                break;
        }
        return $ret;
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
    function writelog($content, $append = true, $prefix = '', $dir = 'logs', $format = 'array')
    {
        Logger::log($content, $append, $prefix, $dir, $format);
    }

}

if (!function_exists('arrayToXml')) {

    /**
     * 数组转XML
     * @param        $array
     * @param string $root 根节点
     * @param bool   $replaceSpaces 处理键名 默认情况下，数组键名中的所有空格都将转换为下划线。如果要不使用此功能，可将第三个参数设置为false
     * @param null   $xmlEncoding XML指定编码
     * @param string $xmlVersion XML版本号
     * @param array  $domProperties 指定DOM属性
     * @return string
     */
    function arrayToXml($array, $root = '', $replaceSpaces = true, $xmlEncoding = null, $xmlVersion = '1.0', array $domProperties = [])
    {
        return yiqiniu\library\ArrayToXml::convert($array, $root, $replaceSpaces, $xmlEncoding, $xmlVersion, $domProperties);
    }
}


if (!function_exists('xmlToArray')) {

    /**
     * 数组转XML
     * @param string $xml 解决的xml文件
     * @return array
     */
    function xmlToArray($xml)
    {
        return \yiqiniu\library\XmlToArray::convert($xml);
    }
}

