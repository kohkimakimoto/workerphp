<?php
namespace Kohkimakimoto\Worker\Job;

use Cron\CronExpression;
use DateTime;

/**
 * Job
 */
class Job
{
    protected $id;

    protected $name;

    protected $command;

    protected $config;

    protected $lastRunTime;

    protected $nextRunTime;

    protected $lockFile;

    protected $eventLoop;

    protected $cronTime;

    protected $numberOfProcesses;

    protected $runtimeJobs = [];

    public function __construct($id, $name, $command, $config)
    {
        $this->id = $id;
        $this->name = $name;
        $this->config = $config;

        if (is_string($command)) {
            // Command line string.
            $this->command = $command;
        } elseif ($command instanceof \Closure) {
            // Closure code.
            $this->command = $command;
        } elseif (is_array($command)) {
            // array
            if (isset($command["command"])) {
                $this->command = $command["command"];
            }

            if (isset($command["cron_time"])) {
                $this->cronTime = $command["cron_time"];
            }

            if (isset($command["number_of_processes"])) {
                $this->numberOfProcesses = $command["number_of_processes"];
            }

        } else {
            throw new \InvalidArgumentException("Unsupported type of 'command'.");
        }

        if ($this->cronTime) {
            $this->cronExpression = CronExpression::factory($this->cronTime);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasCronTime()
    {
        if ($this->cronTime) {
            return true;
        } else {
            return false;
        }
    }

    public function makeRuntimeJob()
    {
        $runtimeJob = new RuntimeJob($this->config, $this);
        $this->runtimeJobs[] = $runtimeJob;
        return $runtimeJob;
    }

    public function updateNextRunTime()
    {
        $this->nextRunTime = $this->cronExpression->getNextRunDate($this->lastRunTime);
    }

    public function secondsUntilNextRuntime($from = null)
    {
        if (!$from) {
            $from = new DateTime();
        }

        $ret = $this->nextRunTime->getTimestamp() - $from->getTimestamp();
        if ($ret < 0) {
            $ret = 0;
        }

        return $ret;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLastRunTime($lastRunTime)
    {
        $this->lastRunTime = $lastRunTime;
    }

    public function getLastRunTime()
    {
        return $this->lastRunTime;
    }

    public function getNextRunTime()
    {
        return $this->nextRunTime;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getRuntimJobs()
    {
        return $this->runtimeJobs;
    }
}
