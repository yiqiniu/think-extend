<?php


namespace yiqiniu;


use think\Route;
use yiqiniu\filesystem\Oss;
use yiqiniu\filesystem\Qiniu;

class Service extends \think\Service
{
    public function register()
    {
        $this->app->bind('qiniu', Qiniu::class);
        $this->app->bind('oss', Oss::class);
    }
}