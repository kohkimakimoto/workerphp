<?php
namespace Kohkimakimoto\Worker\Stats;

use Kohkimakimoto\Worker\Foundation\WorkerStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatsEventListener implements EventSubscriberInterface
{
    public function detectedWorkerStarted(WorkerStartedEvent $event)
    {
        $worker = $event->getWorker();

        $worker->stats->setBootTime(new \DateTime());
        if ($worker->stats->isOn()) {
            $worker->eventLoop->addPeriodicTimer($worker->stats->getInterval(), function () use ($worker) {

                $uptime = $worker->stats->getUptime();

                if ($uptime > 60) {
                    $uptime = round(($uptime / 60), 0)." minutes";
                } else {
                    $uptime = $uptime." seconds";
                }

                $mem = memory_get_usage();
                $memM = round(($mem / 1024 / 1024), 1);
                $worker->output->writeln("<info>Stats report:</info> memory_usage: <comment>$mem</comment> bytes ($memM MB). uptime: $uptime. at ".(new \DateTime())->format('Y-m-d H:i:s'));

            });
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'worker.started' => 'detectedWorkerStarted',
        );
    }
}
