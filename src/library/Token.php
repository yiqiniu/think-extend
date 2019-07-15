<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace yiqiniu\library;


use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

class Token
{
    private static $key = '90dc8223529f484b90e1b6322df3a968';

    /**
     * @param $data 加密的数据
     * @param int $is_exp 是否加入有效时间
     * @param int $time 有效时长
     * @return string
     */
    public static function getToken($data, $is_exp = 1, $time = 86400)
    {
        $token['iss'] = request()->Domain();
        $token['aud'] = request()->Domain();
        $token['iat'] = time();
        $token['aud'] = time();
        if ($is_exp) {
            $token['exp'] = time() + $time;
        }
        $token['data'] = $data;
        $jwt = JWT::encode($token, self::$key);
        return $jwt;
    }

    /**
     * 验证签名
     * @param $jwt jwt字符串
     * @param $app 客户端ID
     * @return array 数据
     * @throws \Exception
     */
    public static function verifyToken($jwt, $app)
    {
        try {
            $jwt = strpos($jwt, ' ') !== false ? explode(' ', $jwt)[1] : $jwt;
            JWT::$timestamp = time();//当前时间
            $decoded = JWT::decode($jwt, self::$key, ['HS256']); //HS256方式，这里要和签发的时候对应
            if (empty($decoded->data) || $decoded->data->app != $app) {
                exception_api(401, '长时间未操作，请重新登录');
            }
            return (array)$decoded->data;
        } catch (SignatureInvalidException $e) {
            exception_api(401, "长时间未操作，请重新登录");//验签失败
        } catch (BeforeValidException $e) { //未捕获的异常
            exception_api(401, $e->getMessage());
        } catch (\UnexpectedValueException $e) {//字符串格式不正确
            exception_api(401, '长时间未操作，请重新登录');
        } catch (ExpiredException $e) { // token过期
            exception_api(401, '长时间未操作，请重新登录');
        } catch (\Exception $e) { //其他错误
            throw $e;
        }
    }

    /**
     * @param $jwt
     * @param $data
     * @return mixed
     */
    public static function verificationOther($jwt, $data)
    {
        try {
            JWT::$timestamp = strtotime(date('Y-m-d H:i:s'));//当前时间
            $decoded = JWT::decode($jwt, self::$key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $tag_data = (array)$decoded->data;
            foreach ($data as $k => $v) {
                if (!array_key_exists($k, $tag_data)) {
                    exception_api(401, '验证失败');
                }
                if ($tag_data[$k] != $data[$k]) {
                    exception_api(401, '验证失败');
                }
            }
            return $data;
        } catch (SignatureInvalidException $e) {
            exception_api(401, "登陆超时");//验签失败
        } catch (BeforeValidException $e) { //未捕获的异常
            exception_api(401, $e->getMessage());
        } catch (\UnexpectedValueException $e) {//字符串格式不正确
            exception_api(401, '长时间未操作，请重新登录');
        } catch (ExpiredException $e) { // token过期
            exception_api(401, '登录凭证失效');
        } catch (\Exception $e) { //其他错误
        }
    }


}