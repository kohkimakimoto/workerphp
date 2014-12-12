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

        $worker['httpController']->boot();
        $worker['httpServer']->boot();
    }

    public function detectedForkedJobProcess(ForkedJobProcessEvent $event)
    {
        $worker = $event->getWorker();

        $worker["httpServer"]->shutdown();
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::STARTED_WORKER => 'detectedStartWorker',
            Events::FORKED_JOB_PROCESS => 'detectedForkedJobProcess',
        );
    }
}
