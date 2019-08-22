<?php


// 处理成功

use yiqiniu\facade\Logger;
use yiqiniu\facade\Token;


if (!function_exists('api_exception')) {
    /**
     * @param int $code 异常代码
     * @param string $msg 异常信息
     * @throws \yiqiniu\exception\ApiException
     */
    function api_exception($code, $msg = '')
    {
        if (!is_numeric($code) && !empty($msg)) {
            $msg2 = $code;
            $code = $msg;
            $msg = $msg2;
        }
        if (!is_numeric($code)) {
            $msg = $code;
            $code = API_EXCEPTION;
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
    function api_result($code, $msg = '', $data = [], $header = [])
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
            'msg' => $msg != '' ? $msg : (API_STATUS_TEXT[$code] ?? ''),
            'time' => time(),
            'data' => $data
        ];

        $response = think\facade\Response::create($result, 'json')->header($header);
        throw new \think\exception\HttpResponseException($response);
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


if (!function_exists('get_browser_type')) {
    /**
     * 检测浏览器类型，成功返回
     * @return string
     *
     */
    function get_browser_type()
    {

        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            //return 'Internet Explorer 9.0';
            return 'internet explorer';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            //return 'Internet Explorer 8.0';
            return 'internet explorer';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            //return 'Internet Explorer 7.0';
            return 'internet explorer';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            //return 'Internet Explorer 6.0';
            return 'internet explorer';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Edge')) {
            //return 'Firefox';
            return 'edge';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
            //return 'Firefox';
            return 'firefox';
        }

        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            //return 'Chrome';
            $str = substr($_SERVER['HTTP_USER_AGENT'], strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') + 7, 2);
            return $str > '45' ? 'chrome' : 'chrome45';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
            // return 'Safari';
            return 'safari';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
            // return 'Opera';
            return 'opera';
        }
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], '360SE')) {
            //   return '360SE';
            return '360se';
        }
    }
}

if (!function_exists('api_refresh_token')) {
    /**
     * 刷新token
     * @throws HttpResponseException
     */
    function api_refresh_token()
    {
        //获取用户的认证信息
        $header = request()->header();
        $refresh_token = request()->post('refresh_token', '');
        if (!empty($refresh_token)) {
            $tokenBody = Token::verifyToken($refresh_token, $header['app'] ?? '');
            $token = Token::getToken($tokenBody);
            return api_result($token);
        } else {
            api_result(API_VAILD_EXCEPTION, 'refresh_token 不能为空');
        }
    }
}

