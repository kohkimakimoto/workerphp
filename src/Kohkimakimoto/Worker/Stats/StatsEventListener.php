<?php
namespace Kohkimakimoto\Worker\Stats;

use Kohkimakimoto\Worker\Foundation\ForkedJobProcessEvent;
use Kohkimakimoto\Worker\Foundation\StartedWorkerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kohkimakimoto\Worker\Foundation\Events;

class StatsEventListener implements EventSubscriberInterface
{
    public function detectedStartWorker(StartedWorkerEvent $event)
    {
        $worker = $event->getWorker();

        if ($worker->stats->isOn()) {
            $worker->eventLoop->addPeriodicTimer(60 * 10, function () use ($worker) {

                $mem = memory_get_usage();
                $worker->output->writeln("<info>Stats report:</info> memory_usage: <comment>$mem</comment> bytes at ".(new \DateTime())->format('Y-m-d H:i:s'));

            });
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::STARTED_WORKER => 'detectedStartWorker',
        );
    }
}
