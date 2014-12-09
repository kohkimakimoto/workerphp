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

    protected $minute = '*';

    protected $hour = '*';

    protected $dayOfMonth = '*';

    protected $month = '*';

    protected $dayOfWeek = '*';

    protected $lockFile;

    protected $eventLoop;

    protected $cronTime;

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
            if (isset($command["onTick"])) {
                $this->command = $command["onTick"];
            }

            if (isset($command["cronTime"])) {
                $this->cronTime = $command["cronTime"];
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

    public function lock()
    {
        $this->lockFile = tempnam(sys_get_temp_dir(), $this->config->name."job.");
    }

    public function unlock()
    {
        if ($this->lockFile) {
            unlink($this->lockFile);
            $this->lockFile = null;
        }
    }

    /**
     * Determin if the job is locked.
     *
     * @return boolean
     */
    public function locked()
    {
        if (!$this->lockFile) {
            return false;
        }

        return file_exists($this->lockFile);
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

    public function getLockFile()
    {
        return $this->lockFile;
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
}