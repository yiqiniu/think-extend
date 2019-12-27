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

        $controller = strtolower($request->controller());
        $action = strtolower($request->action());
        $header = $request->header();
        //加入对APP的处理
        $request->app = $header['app'] ?? '';




        //获取用户的认证信息
        if (isset($header['Authorization'])) {
            $auth = $header['Authorization'];
        } elseif (isset($header['authorization'])) {
            $auth = $header['authorization'];
        } else {
            $auth = '';
        }

        // 不需要认证,直接下一个操作
        // 1.直接在路由中配置的参数,无需参加认证
        // 2.操作不需要认证的
        // 3.控制器不需要认证的
        if ( ($controller == '' && $action == '') ||
            in_array($controller . '/' . $action, $no_auth['action']) ||
            in_array($controller, $no_auth['controller'])) {
            if (!empty($auth)) {
                $this->parseInfo($auth, $request, $header, true);
            }
        } else {
            // 不存在,返回错误
            if (empty($auth)) {
                return api_result(API_TIMEOUT, '登录超时,请重新登录');
            }
            // 解析并到设置request中
            if (!$this->parseInfo($auth, $request, $header)) {
                return api_result(API_TIMEOUT, '非法的用户请求');
            }

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
    protected function parseInfo($authinfo, $request, $header, $noauth = false)
    {
        try {
            $request->tokenBody = Token::verifyToken($authinfo, $header['app'] ?? '');
            return true;
        } catch (Exception $e) {
            if ($noauth) {
                return true;
            } else {
                return api_result($e);
            }

        }
    }
}
