<?php

use Gouguoyin\EasyHttp\Http;
use Gouguoyin\EasyHttp\RequestException;
use Gouguoyin\EasyHttp\Response;
use think\exception\HttpResponseException;
use yiqiniu\exception\ApiException;
use yiqiniu\facade\Logger;
use yiqiniu\facade\Token;


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
        throw  new ApiException($msg, $code);
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
                $result_code = $code->getCode();
            } else {
                Logger::exception($code);
                $result_code = API_ERROR;
            }
            $msg = $code->getMessage();
            $code = $result_code;
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
            'msg' => empty($msg) ? (API_STATUS_TEXT[$code] ?? '未知') : $msg,
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
     * @param string            $url 请求URL
     * @param array             $params 请求参数
     * @param string            $method 请求方法GET/POST
     * @param bool|array|string $upload
     * @param string            $type 返回格式
     * @param array             $header
     * @return array  $data   响应数据
     * @throws \Exception
     * @throws \yiqiniu\exception\ApiException
     */
    function httpRequest($url, $params = [], $method = 'GET', $upload = false, $type = 'json', $header = [])
    {

        $response = [];
        try {
            switch (strtoupper($method)) {
                case 'GET':
                    $response = Http::withHeaders($header)->get($url, $params);
                    break;
                case 'PUT':
                    $response = Http::withHeaders($header)->put($url, $params);
                    break;
                case 'DELETE':
                    $response = Http::withHeaders($header)->delete($url, $params);
                    break;
                case 'PATCH':
                    $response = Http::withHeaders($header)->patch($url, $params);
                    break;
                case 'PAYLOAD':
                    $response = Http::asJson()->post($url, $params);
                    break;
                case 'POST':
                    if ($upload) {
                        $input_name = $upload['name'] ?? 'file';
                        if (is_array($upload)) {
                            $file = $upload['file'] ?? '';
                        } else {
                            $file = $upload;
                        }
                        if (!empty($file) && file_exists($file)) {
                            $info = pathinfo($file);
                            $filename = $info['basename'] ?? '';
                            $response = Http::asMultipart($input_name, fopen($filename, 'rb'), $filename, $header)
                                ->post($url, $params);
                        } else {
                            $response = Http::withHeaders($header)->post($url, $params);
                        }
                    } else {
                        $response = Http::withHeaders($header)->post($url, $params);
                    }
                    break;
                default:
                    throw  new ApiException('CURL不支持的请求方式！', API_VAILD_EXCEPTION);
                    break;
            }

            $result = null;
            switch ($type) {
                case 'json';
                    $result = $response->json();
                    break;
                case 'array';
                    $result = $response->array();
                    break;
                default:
                    $result = $response->body();
                    break;
            }
            return $result;
        } catch (ApiException  $e) {
            throw  $e;
        }

    }
}
if (!function_exists('httpRequest_async')) {
    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param string            $url 请求URL
     * @param callable          $success 成功后的回调
     * @param callable          $fail 失败后回调
     * @param array             $params 请求参数
     * @param string            $method 请求方法GET/POST
     * @param bool|array|string $upload
     * @param string            $type 返回格式
     * @param array             $header
     * @throws \Exception
     * @throws \yiqiniu\exception\ApiException
     */
    function httpRequest_async($url, $success, $fail, $params = [], $method = 'GET', $upload = false, $type = 'json', $header = [])
    {

        //默认成功
        $default_success = static function (Response $response) use ($type, $success) {
            $result = null;
            switch ($type) {
                case 'json';
                    $result = $response->json();
                    break;
                case 'array';
                    $result = $response->array();
                    break;
                default:
                    $result = $response->body();
                    break;
            }
            if ($success !== null && is_callable($success)) {
                $success($result);
            }
        };

        //默认失败
        $default_fail = static function (RequestException $e) use ($fail) {
            if ($fail !== null && is_callable($fail)) {
                $fail(['code' => $e->getCode(), 'msg' => $e->getMessage()]);
            }
        };

        try {
            switch (strtoupper($method)) {
                case 'GET':
                    Http::withHeaders($header)->getAsync($url, $params, $default_success, $default_fail);
                    break;
                case 'PUT':
                    Http::withHeaders($header)->putAsync($url, $params, $default_success, $default_fail);
                    break;
                case 'DELETE':
                    Http::withHeaders($header)->deleteAsync($url, $params, $default_success, $default_fail);
                    break;
                case 'PATCH':
                    Http::withHeaders($header)->patchAsync($url, $params, $default_success, $default_fail);
                    break;
                case 'PAYLOAD':
                    Http::asJson()->postAsync($url, $params, $default_success, $default_fail);
                    break;
                case 'POST':
                    if ($upload) {
                        $input_name = $upload['name'] ?? 'file';
                        if (is_array($upload)) {
                            $file = $upload['file'] ?? '';
                        } else {
                            $file = $upload;
                        }
                        if (!empty($file) && file_exists($file)) {
                            $info = pathinfo($file);
                            $filename = $info['basename'] ?? '';
                            Http::asMultipart($input_name, fopen($filename, 'rb'), $filename, $header)
                                ->postAsync($url, $params, $default_success, $default_fail);
                        } else {
                            Http::withHeaders($header)->postAsync($url, $params, $default_success, $default_fail);
                        }
                    } else {
                        Http::withHeaders($header)->postAsync($url, $params, $default_success, $default_fail);
                    }
                    break;
                default:
                    throw  new ApiException('CURL不支持的请求方式！', API_VAILD_EXCEPTION);
                    break;
            }


        } catch (ApiException  $e) {
            throw  $e;
        }

    }
}
if (!function_exists('writelog')) {
    /**
     * 写入日志
     * @param        $content  mixed  要写入的日志
     * @param bool   $append
     * @param string $prefix
     * @param string $dir
     * @param string $format
     * @append  boole|string  追加 true   false 不追加
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

if (!function_exists('parse_name')) {
    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string $name    字符串
     * @param int    $type    转换类型
     * @param bool   $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    function parse_name(string $name, int $type = 0, bool $ucfirst = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
    }
}

