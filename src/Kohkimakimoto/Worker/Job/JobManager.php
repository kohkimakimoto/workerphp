<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Worker;
use Symfony\Component\Process\Process;

class JobManager
{
    protected $jobs = [];

    protected $config;

    protected $output;

    protected $eventLoop;

    public function __construct($config, $output, $eventLoop)
    {
        $this->config = $config;
        $this->output = $output;
        $this->eventLoop = $eventLoop;
    }

    public function register($name, $command)
    {
        // checks if the same name exists.
        if (array_key_exists($name, $this->jobs)) {
            throw new \InvalidArgumentException("'$name' is already registered as a job.");
        }

        $id = count($this->jobs);
        $this->jobs[$name] = new Job($id, $name, $command, $this->config);

        return $this;
    }

    public function boot()
    {
        // All registered jobs is initialized.
        $bootTime = new \DateTime();
        foreach ($this->jobs as $job) {
            $this->output->writeln("<info>Initializing job:</info> <comment>".$job->getName()."</comment> (job_id: <comment>".$job->getId()."</comment>)");
            $job->setLastRunTime($bootTime);

            if ($job->hasCronTime()) {
                $this->addJobAsTimer($job);
            }
        }
    }

    public function unlockAllJobs()
    {
        if ($this->output->isDebug()) {
            $this->output->writeln("[debug] Try to unlock all jobs");
        }

        foreach ($this->jobs as $job) {
            if ($job->locked()) {
                $file = $job->getLockFile();
                $job->unlock();
                if ($this->output->isDebug()) {
                    $this->output->writeln("[debug] Job unlock: removed file '".$file."' (job_id: ".$job->getId().")");
                }
            } else {
                $this->output->writeln("[debug] The job (job_id: ".$job->getId().") already unlocked. ");
            }
        }
    }

    protected function addJobAsTimer($job)
    {
        $job->updateNextRunTime();
        $worker = $this;
        $secondsOfTimer = $job->secondsUntilNextRuntime();
        $this->eventLoop->addTimer($secondsOfTimer, function () use ($job, $worker) {

            $id = $job->getId();
            $output = $worker->output;

            $now = new \DateTime();

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
                // Child process
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

    public function getJobs()
    {
        return $this->jobs;
    }
}
