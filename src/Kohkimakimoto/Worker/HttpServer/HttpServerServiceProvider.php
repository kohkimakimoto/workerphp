<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\Foundation\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['httpRouter'] = function ($worker) {
            return new HttpRouter(
                $worker['eventLoop']
            );
        };

        $worker['httpServer'] = function ($worker) {
            return new HttpServer(
                $worker,
                $worker['output'],
                $worker['eventLoop'],
                $worker['httpRouter']
            );
        };

        $worker->dispatcher->addSubscriber(new HttpServerEventListener());
    }
}
