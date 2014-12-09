<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Kohkimakimoto\Worker\ServiceProvider;
use Kohkimakimoto\Worker\Worker;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register(Worker $worker)
    {
    }

    public function start(Worker $worker)
    {

    }
}


/*
if ($this->httpServerPort) {
    $socketServer = new ReactSocketServer($this->eventLoop);
    $httpServer = new ReactHttpServer($socketServer);
    $socketServer->listen($this->httpServerPort, $this->httpServerHost);
}
*/
