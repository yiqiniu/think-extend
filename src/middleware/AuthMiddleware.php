<?php

namespace yiqiniu\middleware;


use think\Exception;
use think\facade\Config;
use yiqiniu\facade\Token;


class AuthMiddleware
{
    /**
     * @param $request
     * @param \Closure $next
     * @return mixed|void
     * @throws \HttpResponseException
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {

        $no_auth = Config::get('yqnapi.no_auth');

        // 不需要认证,直接下一个操作
        if (in_array($request->action(), $no_auth['action']) ||
            in_array($request->controller(), $no_auth['controller'])) {
            return $next($request);

        }
        //获取用户的认证信息
        $header = $request->header();
        if (isset($header['Authorization'])) {
            $auth = $header['Authorization'];
        } elseif (isset($header['authorization'])) {
            $auth = $header['authorization'];
        } else {
            $auth = '';
        }

        // 不存在,返回错误
        if (empty($auth)) {
            return api_result(API_ERROR, '登录超时,请重新登录');
        }
        // 解析并到设置request中
        if (!$this->parseInfo($auth, $request, $header)) {
            return api_result(API_ERROR, '非法的用户请求');
        }
        return $next($request);
    }

    /**
     * 解析用户的
     * @param string $authinfo
     * @param $request
     * @return bool
     * @throws \Exception
     */
    protected function parseInfo($authinfo, $request, $header)
    {
        try {
            $request->tokenBody = Token::verifyToken($authinfo, $header['app'] ?? '');
            return true;

        } catch (Exception $e) {

            return api_result($e);
        }
    }
}
