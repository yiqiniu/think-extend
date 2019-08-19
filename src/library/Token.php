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
use yiqiniu\exception\ApiException;

class Token
{
    private $key = '';

    /**
     * Token constructor.
     * @param string $key
     */
    public function __construct($key = '')
    {
        $this->key = empty($key) ? config('yqnapi.auth.token_key') : $key;
    }


    /**
     * 检查 是否设置Token Key
     * @throws \yiqiniu\exception\ApiException
     */
    private function checkKey()
    {
        if (empty($this->key))
            api_exception('no set token key');
        return true;
    }

    /**
     * @param $data 加密的数据
     * @param int $is_exp 是否加入有效时间
     * @return array
     * @throws ApiException
     */
    public function getToken($data, $is_exp = 1)
    {
        try {
            $this->checkKey();

            $time = time(); //当前时间

            //公用信息
            $token = [
                'iss' => request()->Domain(), //签发者 可选
                'iat' => $time, //签发时间
                'data' => $data
            ];

            $access_token = $token;
            $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
            if ($is_exp) {
                $access_token['exp'] = $time + TOKEN_AUTH_TIME; //access_token过期时间,这里设置6个小时
            }


            $refresh_token = $token;
            $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
            if ($is_exp) {
                $refresh_token['exp'] = $time + REFRESH_TOKEN_TIMEOUT; //access_token过期时间,这里设置30天
            }


            return [
                'access_token' => JWT::encode($access_token, $this->key),
                'refresh_token' => JWT::encode($refresh_token, $this->key),
            ];

        } catch (ApiException $e) {
            throw  $e;
        } catch (\Exception $e) {
            throw  $e;
        }
    }


    /**
     * 验证签名
     * @param $jwt jwt字符串
     * @param $app 客户端ID
     * @return array 数据
     * @throws \Exception
     */
    public function verifyToken($jwt, $app)
    {
        try {
            $this->checkKey();
            $jwt = strpos($jwt, ' ') !== false ? explode(' ', $jwt)[1] : $jwt;
            JWT::$timestamp = time();//当前时间
            $decoded = JWT::decode($jwt, $this->key, ['HS256']); //HS256方式，这里要和签发的时候对应
            if (empty($decoded->data) || (isset($decoded->data->app) && $decoded->data->app != $app)) {
                api_exception(API_TIMEOUT, '登录信息无效,请重新登录');
            }
            return (array)$decoded->data;
        } catch (SignatureInvalidException $e) {
            api_exception(API_TIMEOUT, "登录审核证书已过期,请重新登录");//验签失败
        } catch (BeforeValidException $e) { //未捕获的异常
            api_exception(API_TIMEOUT, $e->getMessage());
        } catch (\UnexpectedValueException $e) {//字符串格式不正确
            api_exception(API_TIMEOUT, '无效的授权，请重新登录');
        } catch (ExpiredException $e) { // token过期
            api_exception(API_TIMEOUT, '授权已过期，请重新登录');
        } catch (ApiException $e) {
            api_exception(API_TIMEOUT, $e->getMessage());
        } catch (\Exception $e) { //其他错误
            api_exception(API_TIMEOUT, $e->getMessage());
        }
    }

    /**
     * @param $jwt
     * @param $data
     * @return mixed
     */
    public function verificationOther($jwt, $data)
    {
        try {
            $this->checkKey();
            JWT::$timestamp = strtotime(date('Y-m-d H:i:s'));//当前时间
            $decoded = JWT::decode($jwt, $this->key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $tag_data = (array)$decoded->data;
            foreach ($data as $k => $v) {
                if (!array_key_exists($k, $tag_data)) {
                    api_exception(API_TIMEOUT, '验证失败');
                }
                if ($tag_data[$k] != $data[$k]) {
                    api_exception(API_TIMEOUT, '验证失败');
                }
            }
            return $data;
        } catch (ApiException $e) {
            throw  $e;
        } catch (SignatureInvalidException $e) {
            api_exception(API_TIMEOUT, "登陆超时");//验签失败
        } catch (BeforeValidException $e) { //未捕获的异常
            api_exception(API_TIMEOUT, $e->getMessage());
        } catch (\UnexpectedValueException $e) {//字符串格式不正确
            api_exception(API_TIMEOUT, '长时间未操作，请重新登录');
        } catch (ExpiredException $e) { // token过期
            api_exception(API_TIMEOUT, '登录凭证失效');
        } catch (\Exception $e) { //其他错误
            api_exception($e->getMessage());
        }
    }


}