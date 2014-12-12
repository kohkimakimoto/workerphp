<?php
namespace Kohkimakimoto\Worker\Http;

use Kohkimakimoto\Worker\Foundation\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
        $worker['httpController'] = function ($worker) {
            return new HttpController(
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

        $worker->dispatcher->addSubscriber(new HttpEventListener());
    }

    public function start(Worker $worker)
    {
        $worker['httpController']->boot();
        $worker['httpServer']->boot();
    }
}
