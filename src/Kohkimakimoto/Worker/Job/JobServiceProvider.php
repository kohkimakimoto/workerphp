<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class JobServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
    }

    public function start(Worker $worker)
    {
        $worker['job']->boot();
    }

    public function shutdown(Worker $worker)
    {
    }
}
