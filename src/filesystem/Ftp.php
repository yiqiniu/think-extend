<?php


namespace yiqiniu\filesystem;


use League\Flysystem\AdapterInterface;
use think\filesystem\Driver;

class Ftp extends Driver
{

    protected $config = [
        'host' => '',
        'port' => '21',
        'username' => '',
        'password' => '',
        'ssl' => false,
        'timeout' => 30,
        'root' => '/',
    ];

    protected function createAdapter(): AdapterInterface
    {
        try {
            return new \League\Flysystem\Adapter\Ftp($this->config);
        } catch (\Exception $e) {
            return null;
        }
    }
}