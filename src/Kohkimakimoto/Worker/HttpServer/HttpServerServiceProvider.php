<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\Foundation\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['httpController'] = function ($worker) {
            return new HttpServerController(
                $worker['eventLoop'],
                $worker['config'],
                $worker['output'],
                $worker['job']
            );
        };

        $worker['httpServer'] = function ($worker) {
            return new HttpServer(
                $worker['output'],
                $worker['eventLoop'],
                $worker['httpController']
            );
        };

        $worker->dispatcher->addSubscriber(new HttpServerEventListener());
    }
}
