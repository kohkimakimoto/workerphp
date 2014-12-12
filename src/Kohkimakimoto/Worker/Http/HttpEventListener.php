<?php
namespace Kohkimakimoto\Worker\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class HttpEventListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }


    public function detectedForkedJobProcess($event)
    {
    }

    public function detectedStartWorker($event)
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::FORKED_JOB_PROCESS => 'detectedForkedJobProcess',
            Events::STARTED_WORKER => 'detectedStartWorker'
        );
    }
}
