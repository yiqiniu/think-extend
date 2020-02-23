<?php


namespace yiqiniu\swoole;


use Swoole\Server as SocketServer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\swoole\PidManager;

abstract  class CommandService extends Command
{

    public function configure()
    {

        $this->setName($this->command['name'])
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->setDescription($this->command['description']);
    }

    protected function initialize(Input $input, Output $output)
    {
        $this->app->bind(\Swoole\Server::class, function () {
            return $this->createSwooleServer();
        });

        $this->app->bind(PidManager::class, function () {
            return new PidManager($this->app->config->get($this->server_name . ".server.options.pid_file"));
        });
    }

    public function handle()
    {

        $this->checkEnvironment();

        $action = $this->input->getArgument('action');

        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->app->invokeMethod([$this, $action], [], true);
        } else {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
        }
    }

    /**
     * 检查环境
     */
    protected function checkEnvironment()
    {
        if (!extension_loaded('swoole')) {
            $this->output->error('Can\'t detect Swoole extension installed.');

            exit(1);
        }

        if (!version_compare(swoole_version(), '4.3.1', 'ge')) {
            $this->output->error('Your Swoole version must be higher than `4.3.1`.');

            exit(1);
        }
    }


    /**
     * 启动server
     * @access protected
     * @param PidManager $pidManager
     * @return void
     */
    protected function start(PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->output->writeln('<error>'.$this->server_name.' server process is already running.</error>');
            return;
        }

        $manager = $this->app->make($this->socket_class);


        $this->output->writeln('Starting '.$this->server_name.' server...');

        $host = $manager->getConfig('server.host');
        $port = $manager->getConfig('server.port');

        $this->output->writeln("$this->server_name server started: <http://{$host}:{$port}>");
        $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        $manager->run();
    }

    /**
     * 柔性重启server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function reload(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>no '.$this->server_name.' server process running.</error>');
            return;
        }

        $this->output->writeln('Reloading '.$this->server_name.' server...');

        if (!$manager->killProcess(SIGUSR1)) {
            $this->output->error('> failure');

            return;
        }

        $this->output->writeln('> success');
    }

    /**
     * 停止server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function stop(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>no '.$this->server_name.' server process running.</error>');
            return;
        }

        $this->output->writeln('Stopping '.$this->server_name.' server...');

        $isRunning = $manager->killProcess(SIGTERM, 15);

        if ($isRunning) {
            $this->output->error('Unable to stop the '.$this->server_name.'_server process.');
            return;
        }

        $this->output->writeln('> success');
    }


    /**
     * 重启server
     * @access protected
     * @param Manager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function restart(PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->stop($pidManager);
        }

        $this->start($pidManager);
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $config      = $this->app->config;
        $host        = $config->get($this->server_name.'.server.host');
        $port        = $config->get($this->server_name.'.server.port');
        $socketType  = $config->get($this->server_name.'.server.sock_type', SWOOLE_SOCK_TCP);
        $mode        = $config->get($this->server_name.'.server.mode', SWOOLE_PROCESS);

        /** @var \Swoole\Server $server */
        $server = new SocketServer($host, $port, $mode, $socketType);

        $options = $config->get($this->server_name.'.server.options');

        $server->set($options);
        return $server;
    }
}