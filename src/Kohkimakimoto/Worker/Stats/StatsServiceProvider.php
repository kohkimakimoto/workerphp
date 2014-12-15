<?php
namespace Kohkimakimoto\Worker\Stats;

use Kohkimakimoto\Worker\Foundation\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class StatsServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['stats'] = function ($worker) {
            return new StatsReporter();
        };

        $worker->dispatcher->addSubscriber(new StatsEventListener());
    }
}
