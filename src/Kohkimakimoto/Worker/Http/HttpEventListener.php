<?php
namespace Kohkimakimoto\Worker\Http;

use Kohkimakimoto\Worker\Foundation\ForkedJobProcessEvent;
use Kohkimakimoto\Worker\Foundation\StartedWorkerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class HttpEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function detectedStartWorker(StartedWorkerEvent $event)
    {
        $worker = $event->getWorker();

        $worker->httpServer->boot();
        $worker->httpController->boot();
    }

    public function detectedForkedJobProcess(ForkedJobProcessEvent $event)
    {
        $worker = $event->getWorker();

        $worker["httpServer"]->shutdown();
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::WORKER_STARTED => 'detectedStartWorker',
            Events::JOB_FORKED_PROCESS => 'detectedForkedJobProcess',
        );
    }
}
