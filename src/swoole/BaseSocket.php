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

use Swoole\Runtime;
use Swoole\Server;
use Swoole\Server\Task;
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
     * @param App $container
     * @param PidManager $pidManager
     */
    public function __construct(App $container, PidManager $pidManager)
    {
        $this->container = $container;
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
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $name, $default = null)
    {
        return $this->container->config->get($this->server_name . ".{$name}", $default);
    }

    /**
     * "onWorkerStart" listener.
     *
     * @param \Swoole\Http\Server|mixed $server
     *
     * @throws Exception
     */
    public function onWorkerStart($server)
    {
        Runtime::enableCoroutine(
            $this->getConfig('coroutine.enable', true),
            $this->getConfig('coroutine.flags', SWOOLE_HOOK_ALL)
        );

        $this->clearCache();

        $this->setProcessName($server->taskworker ? 'task process' : 'worker process');

        $this->prepareApplication();

        $this->triggerEvent("workerStart", $this->app);
        $this->onWorkStartAction($server);
    }
    /**
     * Set onTask listener.
     *
     * @param mixed $server
     * @param Task $task
     */
    public function onTask($server, Task $task)
    {
        $this->runInSandbox(function () use ($server, $task) {
            $this->onTaskAciton($server, $task);
        });
    }
    /**
     *
     * @param $serv
     */
    abstract public function onWorkStartAction($serv);



    /**
     * 异步处理任务
     * @param $serv
     * @param $task_id      任务ID
     * @param $from_id
     * @param $data
     */
    abstract protected function onTaskAciton($serv, Task $task);

    /**
     * 异步任务结束时
     * @param $serv
     * @param $task_id
     * @param $data
     */
    abstract protected function onFinish($serv, $task_id, $data);
}
