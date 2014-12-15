<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\Foundation\WorkerEvents;
use Kohkimakimoto\Worker\Job\JobForkedProcessEvent;
use Kohkimakimoto\Worker\Foundation\WorkerStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Job\JobEvents;

class HttpServerEventListener implements EventSubscriberInterface
{
    public function detectedWorkerStarted(WorkerStartedEvent $event)
    {
        $worker = $event->getWorker();
        $worker->httpServer->boot();
    }

    public function detectedJobForkedProcess(JobForkedProcessEvent $event)
    {
        $worker = $event->getWorker();
        $worker->httpServer->shutdown();
    }

    public static function getSubscribedEvents()
    {
        return array(
            WorkerEvents::STARTED => 'detectedWorkerStarted',
            JobEvents::FORKED_PROCESS => 'detectedJobForkedProcess',
        );
    }
}
