<?php
namespace Kohkimakimoto\Worker\Foundation;

use Kohkimakimoto\Worker\Worker;
use Symfony\Component\EventDispatcher\Event;

class ForkedJobProcessEvent extends Event
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
