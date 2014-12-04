<?php
namespace Kohkimakimoto\Worker;

use Cron\CronExpression;
use DateTime;

/**
 * Job
 */
class Job
{
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

    protected $schedule;

    public function __construct($schedule, $command)
    {
        $this->command = $command;
        $this->schedule = $schedule;
    }

    public function init(Worker $worker)
    {
        $this->lastRunTime = new DateTime();
        $this->worker = $worker;
        $this->cronExpression = CronExpression::factory($this->schedule);

        $this->updateNextRunTime();
    }

    public function locked()
    {
        if (!$this->lockFile) {
            return false;
        }

        return file_exists($this->lockFile);
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

    public function getLockFile()
    {
        return $this->lockFile;
    }

    public function getSchedule()
    {
        return $this->schedule;
    }

    public function isReadyToRun($date)
    {
        return ($this->nextRunTime <= $date);
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

    public function updateNextRunTime()
    {
        $this->nextRunTime = $this->cronExpression->getNextRunDate($this->lastRunTime);
    }
}
