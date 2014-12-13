<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Foundation\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class JobServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['job'] = new JobManager(
            $worker,
            $worker['dispatcher'],
            $worker['config'],
            $worker['output'],
            $worker['eventLoop']
        );

        $worker->dispatcher->addSubscriber(new JobEventListener());
    }
}
