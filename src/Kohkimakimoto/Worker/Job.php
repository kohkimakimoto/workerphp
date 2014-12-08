<?php
namespace Kohkimakimoto\Worker;

use Cron\CronExpression;
use DateTime;

/**
 * Job
 */
class Job
{
    protected $id;

    protected $command;

    protected $lastRunTime;

    protected $nextRunTime;

    protected $minute = '*';

    protected $hour = '*';

    protected $dayOfMonth = '*';

    protected $month = '*';

    protected $dayOfWeek = '*';

    protected $lockFile;

    protected $worker;

    protected $eventLoop;

    protected $schedule;

    public function __construct($id, $schedule, $command, $worker)
    {
        $this->id = $id;
        $this->command = $command;
        $this->schedule = $schedule;
        $this->cronExpression = CronExpression::factory($this->schedule);
        $this->worker = $worker;
    }

    public function lock()
    {
        $this->lockFile = tempnam(sys_get_temp_dir(), $this->worker->getName().".job.");
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

    public function getSchedule()
    {
        return $this->schedule;
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
