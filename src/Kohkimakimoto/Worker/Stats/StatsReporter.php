<?php
namespace Kohkimakimoto\Worker\Stats;

class StatsReporter
{
    protected $on = false;

    protected $interval;

    protected $bootTime;

    public function on($interval = 60)
    {
        $this->on = true;
        $this->interval = $interval;
    }

    public function isOn()
    {
        return $this->on;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function setBootTime($bootTime)
    {
        $this->bootTime = $bootTime;
    }

    public function getBootTime()
    {
        return $this->bootTime;
    }

    public function getUptime($date = null)
    {
        if (!$date) {
            $date = new \DateTime();
        }
        $uptime = $date->getTimestamp() - $this->bootTime->getTimestamp();
        return $uptime;
    }
}
