<?php


namespace yiqiniu;


use think\Route;
use yiqiniu\console\command\MakeFacade;
use yiqiniu\console\command\MakeLoaderClass;
use yiqiniu\console\command\ModelAll;
use yiqiniu\console\command\Socket;
use yiqiniu\console\command\UuidKey;
use yiqiniu\console\command\ValidateAll;
use yiqiniu\filesystem\Oss;
use yiqiniu\filesystem\Qiniu;

class Service extends \think\Service
{
    public function register()
    {
        $this->app->bind('qiniu', Qiniu::class);
        $this->app->bind('oss', Oss::class);
    }

    public function boot(Route $route)
    {

        // 开启刷新token的请求
        if($this->app->config->get('extend.refresh_token',0)){
            $route->post('refresh_token', function () {
                api_refresh_token();
            });
        }
        // 生成命令
        $this->commands([
            'yqn:server'=>Socket::class,
            'yqn:model' =>ModelAll::class,
            'yqn:validate' =>ValidateAll::class,
            'yqn:facade'=>MakeFacade::class,
            'yqn:uuid'=>UuidKey::class,
            'yqn:loader'=>MakeLoaderClass::class,
        ]);
    }

}