<?php


namespace yiqiniu\filesystem;


use League\Flysystem\AdapterInterface;
use Liz\Flysystem\QiNiu\QiNiuOssAdapter;
use think\File;
use think\filesystem\Driver;

class Qiniu extends  Driver
{

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'accesskey' => '',
        'secretKey' => '',
        'bucket' => '',
        'cdnhost' => '',
    ];

    protected function createAdapter(): AdapterInterface
    {
        return new QiNiuOssAdapter($this->config['accesskey'],
            $this->config['secretKey'],
            $this->config['bucket'],
            $this->config['cdnhost']
        );
    }

    /**
     * 保存文件
     * @param string               $path
     * @param File                 $file
     * @param null|string|\Closure $rule
     * @param array                $options
     * @return bool|string
     */
    public function putFile(string $path, File $file, $rule = null, array $options = [])
    {
        $this->createDir($path);


        return $this->putFileAs($path, $file, $file->hashName($rule), $options);
    }
}