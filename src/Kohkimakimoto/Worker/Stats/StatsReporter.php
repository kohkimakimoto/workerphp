<?php
namespace Kohkimakimoto\Worker\Stats;

class StatsReporter
{
    protected $on = false;

    protected $interval;

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
}
