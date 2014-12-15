<?php
namespace Kohkimakimoto\Worker\Foundation;

use Kohkimakimoto\Worker\Worker;
use Symfony\Component\EventDispatcher\Event;

class WorkerShuttingDownEvent extends Event
{
    private $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    public function getWorker()
    {
        return $this->worker;
    }
}
