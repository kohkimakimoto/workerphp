<?php
namespace Kohkimakimoto\Worker;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use React\Http\Server as ReactHttpServer;
use React\Socket\Server as ReactSocketServer;
use Kohkimakimoto\Worker\EventLoop\Factory;
use DateTime;

/**
 * Worker
 */
class Worker extends Container
{
    protected $name;

    protected $output;

    protected $jobs = array();

    protected $childPids = array();

    protected $isMaster;

    protected $eventLoop;

    protected $httpServerPort;

    protected $httpServerHost;

    protected $providers;

    protected $finished;

    /**
     * Constructor.
     *
     * @param array $config configuration parameters.
     */
    public function __construct($config = array())
    {
        $this["config"] = new Config($config);
        $this->name = $this["config"]->name;

        $this["output"] = new ConsoleOutput();
        if ($this["config"]->isDebug) {
            $this["output"]->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
        $this->output = $this["output"];

        $this->isMaster = true;
        $this->finished = false;

        $this->eventLoop = Factory::create();

        $this->providers = [];
        $this->registerDefaultProviders();
    }

    protected function registerDefaultProviders()
    {
        $providers = [
            'Kohkimakimoto\Worker\Job\JobServiceProvider',
            'Kohkimakimoto\Worker\HttpServer\HttpServerServiceProvider',
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

        $this->output->writeln("<info>Starting <comment>".$this->name."</comment>.</info>");

        foreach($this->providers as $providers) {
            $providers->start($this);
        }
    }

    public function startBackup()
    {
        declare (ticks = 1);
        register_shutdown_function(array($this, "shutdown"));
        pcntl_signal(SIGTERM, array($this, "signalHandler"));
        pcntl_signal(SIGINT, array($this, "signalHandler"));

        $this->output->writeln("<info>Starting <comment>".$this->name."</comment>.</info>");

        // All registered jobs is initialized.
        $bootTime = new DateTime();
        foreach ($this->jobs as $job) {
            $this->output->writeln("<info>Initializing job:</info> <comment>".$job->getName()."</comment> (job_id: <comment>".$job->getId()."</comment>)");
            $job->setLastRunTime($bootTime);

            if ($job->hasCronTime()) {
                $this->addJobAsTimer($job);
            }
        }

        $this->output->writeln('<info>Successfully booted. Quit working with CONTROL-C.</info>');

        // A dummy timer to keep a process on a system.
        $this->eventLoop->addPeriodicTimer(10, function () {});

        // Start event loop.
        $this->eventLoop->run();
    }

    protected function addJobAsTimer($job)
    {
        $job->updateNextRunTime();
        $worker = $this;
        $secondsOfTimer = $job->secondsUntilNextRuntime();
        $this->eventLoop->addTimer($secondsOfTimer, function () use ($job, $worker) {

            $id = $job->getId();
            $output = $worker->output;

            $now = new DateTime();

            if ($output->isDebug()) {
                $output->writeln("[debug] Try running a job: (job_id: $id) at ".$now->format('Y-m-d H:i:s'));
            }

            if ($job->locked()) {
                if ($output->isDebug()) {
                    $output->writeln("[debug] Skipped: The job is already run (job_id: $id)");
                }

                // add next timer
                $job->setLastRunTime($now);
                $worker->addJobAsTimer($job);

                return;
            }

            $job->lock();
            if ($output->isDebug()) {
                $output->writeln("[debug] Job lock: create file '".$job->getLockFile()."' (job_id: $id).");
            }

            $pid = pcntl_fork();
            if ($pid === -1) {
                // Error
                throw new \RuntimeException("Fork Error.");
            } elseif ($pid) {
                // Parent process
                $worker->childPids[$pid] = $job;

                // add next timer
                $job->setLastRunTime($now);
                $worker->addJobAsTimer($job);
            } else {
                $worker->isMaster = false;
                if ($output->isDebug()) {
                    $output->writeln("[debug] Forked process for (job_id: ".$id.") (pid:".posix_getpid().")");
                }

                $command = $job->getCommand();
                $output->writeln("<info>Running job:</info> <comment>".$job->getName()."</comment> (job_id: <comment>".$id."</comment>) at ".$now->format('Y-m-d H:i:s'));

                if ($command instanceof \Closure) {
                    // command is a closure
                    call_user_func($command, $worker);
                } elseif (is_string($command)) {
                    // command is a string
                    $process = new Process($command);
                    $process->setTimeout(null);

                    $process->run(function ($type, $buffer) use ($output) {
                        $output->write($buffer);
                    });
                } else {
                    throw new \RuntimeException("Unsupported operation.");
                }

                $file = $job->getLockFile();
                $job->unlock();
                if ($output->isDebug()) {
                    $output->writeln("[debug] Job unlock: removed file '".$file."' (job_id: $id).");
                }
                exit;
            }
        });

        if ($this->output->isDebug()) {
            $this->output->writeln("[debug] Added new timer: '".$job->getNextRunTime()->format('Y-m-d H:i:s')."' (after ".$secondsOfTimer." seconds) (job_id: ".$job->getId().").");
        }
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
        foreach($this->providers as $providers) {
            $providers->start($this);
        }

        if ($this->isMaster && !$this->finished) {
            foreach ($this->jobs as $id => $job) {
                if ($job->locked()) {
                    $file = $job->getLockFile();
                    $job->unlock();
                    if ($this->output->isDebug()) {
                        $this->output->writeln("[debug] Job unlock: removed file '".$file."' (job_id: $id).");
                    }
                }
            }
            $this->output->writeln("<info>Shutdown <comment>".$this->name."</comment>.</info>");
            $this->finished = true;
        }
    }

    /**
     * Gets an application name.
     *
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    public function job($name, $command)
    {
        return $this["job"]->register($name, $command);
    }

    public function httpServer($port, $host = '127.0.0.1')
    {
        $this->httpServerPort = $port;
        $this->httpServerHost = $host;
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
