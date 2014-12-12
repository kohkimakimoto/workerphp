<?php
namespace Kohkimakimoto\Worker\Event;

use Kohkimakimoto\Worker\Worker;
use Symfony\Component\EventDispatcher\Event;

class WorkerStartEvent extends Event
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