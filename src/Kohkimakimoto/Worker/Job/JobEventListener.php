<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Foundation\WorkerStartedEvent;
use Kohkimakimoto\Worker\Foundation\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function detectedWorkerStarted(WorkerStartedEvent $event)
    {
        $worker = $event->getWorker();
        $worker->job->boot();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'worker.started' => 'detectedWorkerStarted',
        );
    }
}
