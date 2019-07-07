<?php


namespace yiqiniu\filesystem;


use League\Flysystem\AdapterInterface;
use think\filesystem\Driver;
use Xxtime\Flysystem\Aliyun\OssAdapter;

class Oss extends Driver
{

    protected $config=[
        'accessId'       => '',
        'accessSecret'   => '',
        'bucket'         => '',
        'endpoint'       => '',
        'timeout'        => 3600,
        'connectTimeout' => 10,
        'isCName'        => false,
        'token'          => '',
    ];
    protected function createAdapter(): AdapterInterface
    {
        try {
            return new OssAdapter($this->config);
        } catch (\Exception $e) {
            return null;
        }
    }
}