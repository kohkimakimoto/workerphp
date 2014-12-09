<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class JobServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['job'] = function ($worker) {
            return new JobManager(
                $worker['config'],
                $worker['output'],
                $worker['event_loop']
            );
        };
    }

    public function start(Worker $worker)
    {
        $worker['job']->boot();
    }

    public function shutdown(Worker $worker)
    {
        $worker['job']->unlockAllJobs();
    }

}
