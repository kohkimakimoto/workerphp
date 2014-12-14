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

        $worker['httpServerController'] = function ($worker) {
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
                $worker['httpServerController']
            );
        };

        $worker->dispatcher->addSubscriber(new HttpServerEventListener());
    }
}
