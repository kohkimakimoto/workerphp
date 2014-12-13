<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Foundation\StartedWorkerEvent;
use Kohkimakimoto\Worker\Foundation\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function detectedWorkerStarted(StartedWorkerEvent $event)
    {
        $worker = $event->getWorker();
        $worker->job->boot();
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::WORKER_STARTED => 'detectedWorkerStarted',
        );
    }
}
