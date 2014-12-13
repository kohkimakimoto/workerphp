<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\Job\ForkedJobProcessEvent;
use Kohkimakimoto\Worker\Foundation\StartedWorkerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class HttpServerEventListener implements EventSubscriberInterface
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
