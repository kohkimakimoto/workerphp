<?php
namespace Kohkimakimoto\Worker;

use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Kohkimakimoto\Worker\Foundation\Config;
use Kohkimakimoto\Worker\EventLoop\Factory;
use Kohkimakimoto\Worker\Foundation\Events;
use Kohkimakimoto\Worker\Foundation\WorkerStartedEvent;
use Kohkimakimoto\Worker\Foundation\WorkerShuttingDownEvent;
use Kohkimakimoto\Worker\Foundation\JobEventListener;
use Kohkimakimoto\Worker\Foundation\JobManager;
use Kohkimakimoto\Worker\Foundation\WorkerEvents;

class Worker extends Container
{
    protected $masterPid;

    protected $finished;

    protected $providers = [];

    /**
     * Constructor.
     *
     * @param array $config configuration parameters.
     */
    public function __construct($config = array())
    {
        $this->masterPid = posix_getpid();
        $this->finished = false;

        // Registers fundamental instances.
        $this['config'] = new Config($config);
        $this["eventLoop"] = Factory::create();
        $this["output"] = new ConsoleOutput();
        $this['dispatcher'] = new EventDispatcher();

        if ($this->config->isDebug()) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        // Registers default providers.
        $this->registerDefaultProviders();
    }

    protected function registerDefaultProviders()
    {
        $providers = [
            'Kohkimakimoto\Worker\Job\JobServiceProvider',
            'Kohkimakimoto\Worker\HttpServer\HttpServerServiceProvider',
            'Kohkimakimoto\Worker\Stats\StatsServiceProvider',
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

        $this->dispatcher->dispatch(WorkerEvents::STARTED, new WorkerStartedEvent($this));

        // A dummy timer to keep a process on a system.
        $this->eventLoop->addPeriodicTimer(10, function () {});

        $this->output->writeln('<info>Successfully booted. Quit working with CONTROL-C.</info>');

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
            $this->dispatcher->dispatch(WorkerEvents::SHUTTING_DOWN, new WorkerShuttingDownEvent($this));
            $this->output->writeln("<info>Shutdown <comment>".$this->config->getName()."</comment>.</info>");
            $this->finished = true;
        }
    }

    public function job($name, $command)
    {
        $this->job->register($name, $command);

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
