<?php


namespace yiqiniu\extend;


use think\Route;
use yiqiniu\extend\console\command\MakeFacade;
use yiqiniu\extend\console\command\MakeLoaderClass;
use yiqiniu\extend\console\command\ModelAll;
use yiqiniu\extend\console\command\Socket;
use yiqiniu\extend\console\command\UuidKey;
use yiqiniu\extend\console\command\ValidateAll;
/*use yiqiniu\extend\filesystem\Oss;
use yiqiniu\extend\filesystem\Qiniu;*/

class BootService extends \think\Service
{
    public function register()
    {
    /*    $this->app->bind('qiniu', Qiniu::class);
        $this->app->bind('oss', Oss::class);*/
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