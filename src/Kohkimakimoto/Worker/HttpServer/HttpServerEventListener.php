<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\Job\JobForkedProcessEvent;
use Kohkimakimoto\Worker\Foundation\WorkerStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class HttpServerEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function detectedWorkerStarted(WorkerStartedEvent $event)
    {
        $worker = $event->getWorker();

        $worker->httpServer->boot();
        $worker->httpController->boot();
    }

    public function detectedJobForkedProcess(JobForkedProcessEvent $event)
    {
        $worker = $event->getWorker();

        $worker["httpServer"]->shutdown();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'worker.started' => 'detectedWorkerStarted',
            'job.forked_process' => 'detectedJobForkedProcess',
        );
    }
}
