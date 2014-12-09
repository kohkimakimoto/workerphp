<?php
namespace Kohkimakimoto\Worker;

use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Kohkimakimoto\Worker\EventLoop\Factory;

/**
 * Worker
 */
class Worker extends Container
{
    protected $output;

    protected $eventLoop;

    protected $httpServerPort;

    protected $httpServerHost;

    protected $providers = [];

    protected $finished;

    protected $masterPid;

    /**
     * Constructor.
     *
     * @param array $config configuration parameters.
     */
    public function __construct($config = array())
    {
        $this->masterPid = posix_getpid();
        $this->finished = false;

        $this["event_loop"] = Factory::create();
        $this["output"] = new ConsoleOutput();
        $this["config"] = new Config($config);

        $this->output = $this["output"];
        $this->eventLoop = $this["event_loop"];
        $this->config = $this["config"];

        if ($this->config->isDebug()) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        $this->registerDefaultProviders();
    }

    protected function registerDefaultProviders()
    {
        $providers = [
            'Kohkimakimoto\Worker\Job\JobServiceProvider',
            'Kohkimakimoto\Worker\Http\HttpServerServiceProvider',
        ];

        foreach ($providers as $provider) {
            $this->load($provider);
        }
    }

    public function load($provider)
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }
        $this->providers[] = $provider;
        $provider->register($this);

        return $this;
    }

    /**
     * Starts running worker.
     *
     * @return void
     */
    public function start()
    {
        declare (ticks = 1);
        register_shutdown_function(array($this, "shutdown"));
        pcntl_signal(SIGTERM, array($this, "signalHandler"));
        pcntl_signal(SIGINT, array($this, "signalHandler"));

        $this->output->writeln("<info>Starting <comment>".$this->config->getName()."</comment>.</info>");

        foreach ($this->providers as $provider) {
            $provider->start($this);
        }

        $this->output->writeln('<info>Successfully booted. Quit working with CONTROL-C.</info>');

        // A dummy timer to keep a process on a system.
        $this->eventLoop->addPeriodicTimer(10, function () {});

        // Start event loop.
        $this->eventLoop->run();
    }

    /**
     * Signal handler
     * @param  int  $signo
     * @return void
     */
    public function signalHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
                $this->output->writeln("<fg=red>Got SIGTERM.</fg=red>");
                $this->shutdown();
                exit;

            case SIGINT:
                $this->output->writeln("<fg=red>Got SIGINT.</fg=red>");
                $this->shutdown();
                exit;
        }
    }

    /**
     * Shoutdown process.
     * @return void
     */
    public function shutdown()
    {
        if ($this->masterPid === posix_getpid() && !$this->finished) {
            // only master process.
            foreach ($this->providers as $provider) {
                $provider->shutdown($this);
            }

            $this->output->writeln("<info>Shutdown <comment>".$this->config->getName()."</comment>.</info>");
            $this->finished = true;
        }
    }

    public function job($name, $command)
    {
        $this->job->register($name, $command);

        return $this;
    }

    public function httpServer($port, $host = '127.0.0.1')
    {
        $this->httpServer->bind($port, $host);

        return $this;
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    public function __call($method, $parameters)
    {
        // call_user_func_array($callback, $parameters);
        throw new \BadMethodCallException("Method [$method] does not exist.");
    }
}
