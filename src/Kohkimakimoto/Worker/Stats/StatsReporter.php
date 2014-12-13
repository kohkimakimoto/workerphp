<?php
namespace Kohkimakimoto\Worker\Stats;

class StatsReporter
{
    protected $on = false;

    public function on()
    {
        $this->on = true;
    }

    public function isOn()
    {
        return $this->on;
    }
}
