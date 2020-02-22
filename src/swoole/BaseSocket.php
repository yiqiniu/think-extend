<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace yiqiniu\swoole;

use Swoole\Server;
use think\App;
use think\swoole\App as SwooleApp;
use think\swoole\concerns\InteractsWithPools;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\WithApplication;
use think\swoole\PidManager;
use think\swoole\pool\Cache;
use think\swoole\pool\Db;
use think\swoole\Sandbox;
use yiqiniu\facade\Logger;

/**
 * Class Manager
 */
abstract class BaseSocket
{
    use InteractsWithServer,
        InteractsWithSwooleTable,
        InteractsWithPools,
        WithApplication;

    /**
     * @var App
     */
    protected $container;

    /** @var PidManager */
    protected $pidManager;


    /**
     * @var SwooleApp
     */
    protected $app;
    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'receive',
        'workerStart',
        'workerStop',
        'packet',
        'bufferFull',
        'bufferEmpty',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStart',
        'managerStop',
        'request',
        'task',
        'finish'
    ];

    /**
     * Manager constructor.
     * @param App        $container
     * @param PidManager $pidManager
     */
    public function __construct(App $container, PidManager $pidManager)
    {
        $this->container  = $container;
        $this->pidManager = $pidManager;
    }

    /**
     * Initialize.
     */
    protected function initialize(): void
    {
        $this->prepareTables();
        $this->preparePools();
        $this->setSwooleServerListeners();
    }



    /**
     * 获取配置
     * @param string $name
     * @param null   $default
     * @return mixed
     */
    public function getConfig(string $name, $default = null)
    {
        return $this->container->config->get($this->server_name.".{$name}", $default);
    }

}
