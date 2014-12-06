<?php
namespace Kohkimakimoto\Worker;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use DateTime;

/**
 * Worker
 */
class Worker
{
    const DEFAULT_APP_NAME = 'WorkerPHP';

    protected $name;

    protected $output;

    protected $isDebug;

    protected $options;

    protected $jobs = array();

    protected $childPids = array();

    protected $isMaster;

    /**
     * Constructor.
     *
     * @param array $options configuration parameters.
     */
    public function __construct($options = array())
    {
        $this->isMaster = true;
        $this->finished = false;
        $this->options = $options;
        $this->output = new ConsoleOutput();

        if (isset($this->options["name"])) {
            $this->name = $this->options["name"];
        } else {
            $this->name = self::DEFAULT_APP_NAME;
        }

        if (isset($this->options["is_debug"]) && $this->options["is_debug"]) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
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

        // All registered jobs is initialized.
        foreach ($this->jobs as $id => $job) {
            $this->output->writeln("<info>Initializing a job.</info> (job_id: <comment>$id</comment>)");
            $job->init($this);
        }
        $this->output->writeln('<info>Successfully booted. Quit working with CONTROL-C.</info>');

        // Inifinite loop to keep running.
        while (true) {
            foreach ($this->jobs as $job) {
                $this->fireJobIfNeeded($job);
            }

            sleep(1);
            clearstatcache();
        }
    }

    /**
     * Runs a job if it is needed.
     *
     * @param  [type] $job [description]
     * @return [type] [description]
     */
    public function fireJobIfNeeded($job)
    {
        $id = $job->getId();

        if ($job->locked()) {
            if ($this->output->isDebug()) {
                $this->output->writeln("Skipped: The job is already run (job_id: $id)");
            }

            return;
        }

        $now = new DateTime();

        if ($this->output->isDebug()) {
            $this->output->write("Time: (now: ".$now->format('Y-m-d H:i:s').") ");
            $this->output->writeln("(next run time: ".$job->getNextRunTime()->format('Y-m-d H:i:s').") (job_id: ".$id.")");
        }

        if ($job->isReadyToRun($now)) {
            $job->lock();
            if ($this->output->isDebug()) {
                $this->output->writeln("Job lock: create file '".$job->getLockFile()."' (job_id: $id).");
            }

            $job->setLastRunTime(new DateTime());
            $job->updateNextRunTime();

            $pid = pcntl_fork();
            if ($pid === -1) {
                // Error
                throw new \RuntimeException("Fork Error.");
            } elseif ($pid) {
                // Parent process
                $this->childPids[$pid] = $job;
            } else {
                // Child process
                $this->isMaster = false;
                if ($this->output->isDebug()) {
                    $this->output->writeln("Forked process for (job_id: ".$id.") (pid:".posix_getpid().")");
                }

                $command = $job->getCommand();
                $this->output->writeln("<info>Running a job.</info> (job_id: <comment>".$id."</comment>) at ".$now->format('Y-m-d H:i:s'));

                if ($command instanceof \Closure) {
                    // command is a closure
                    $status = call_user_func($command, $this);

                    $file = $job->getLockFile();
                    $job->unlock();
                    if ($this->output->isDebug()) {
                        $this->output->writeln("Job unlock: removed file '".$file."' (job_id: $id).");
                    }

                    exit($status);
                } elseif (is_string($command)) {
                    // command is a string
                    $process = new Process($command);
                    $process->setTimeout(null);
                    $output = $this->output;
                    $status = $process->run(function ($type, $buffer) use ($output) {
                        $output->write($buffer);
                    });

                    $file = $job->getLockFile();
                    $job->unlock();
                    if ($this->output->isDebug()) {
                        $this->output->writeln("Job unlock: removed file '".$file."' (job_id: $id).");
                    }

                    exit($status);
                } else {
                    throw new \RuntimeException("Unsupported operation.");
                }
            }
        } else {
            if ($this->output->isDebug()) {
                $this->output->write("Skipped: The job is not ready to run (job_id: ".$id.")");
                $this->output->writeln(" schedule: (".$job->getSchedule().")");
            }
        }
    }

    /**
     * Signal handler
     * @param  int $signo
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
        if ($this->isMaster && !$this->finished) {
            foreach ($this->jobs as $id => $job) {
                if ($job->locked()) {
                    $file = $job->getLockFile();
                    $job->unlock();
                    if ($this->output->isDebug()) {
                        $this->output->writeln("Job unlock: removed file '".$file."' (job_id: $id).");
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

    /**
     * Registers a job.
     *
     * @param  string $schedule
     * @param  [type] $command
     * @return Kohkimakimoto\Worker\Worker
     */
    public function job($schedule, $command)
    {
        $id = count($this->jobs);
        $this->jobs[$id] = new Job($id, $schedule, $command);

        return $this;
    }
}
