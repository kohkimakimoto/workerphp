<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class JobServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['job'] = function ($worker) {
            return new JobManager($worker['config']);
        };
    }

    public function start(Worker $worker)
    {
        
    }
}
