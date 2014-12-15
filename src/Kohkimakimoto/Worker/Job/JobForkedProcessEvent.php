<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Worker;
use Symfony\Component\EventDispatcher\Event;

class JobForkedProcessEvent extends Event
{
    private $worker;

    public function __construct(Worker $worker, $job)
    {
        $this->worker = $worker;
        $this->job = $job;
    }

    public function getWorker()
    {
        return $this->worker;
    }

    public function getJob()
    {
        return $this->job;
    }
}
