<?php
namespace Kohkimakimoto\Worker\Foundation;

use Kohkimakimoto\Worker\Foundation\StartedWorkerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class JobEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function detectedStartWorker(StartedWorkerEvent $event)
    {
        $worker = $event->getWorker();

        $worker->job->boot();
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::STARTED_WORKER => 'detectedStartWorker',
        );
    }
}
