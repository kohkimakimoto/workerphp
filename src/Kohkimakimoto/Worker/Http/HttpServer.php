<?php
namespace Kohkimakimoto\Worker\Http;

use React\Http\Server as ReactHttpServer;
use React\Socket\Server as ReactSocketServer;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class HttpServer
{
    protected $output;

    protected $eventLoop;

    protected $port;

    protected $host;

    protected $http;

    public function __construct($output, $eventLoop, $controller)
    {
        $this->output = $output;
        $this->eventLoop = $eventLoop;
        $this->controller = $controller;
    }

    public function bind($port, $host)
    {
        $this->port = $port;
        $this->host = $host;
    }

    public function boot()
    {
        if (!$this->port) {
            return;
        }

        $route = new Route('/', array('controller' => 'MyController'));
        $routes = new RouteCollection();
        $routes->add('route_name', $route);

        $socket = new ReactSocketServer($this->eventLoop);
        $http = new ReactHttpServer($socket);
        $socket->listen($this->port, $this->host);

        $controller = $this->controller;

        $http->on('request', function ($request, $response) use ($controller) {

            $controller->execute($request, $response);

        });

        $this->output->writeln("<info>Initializing http server:</info> <comment>http://".$this->host.":".$this->port."</comment>");
    }
}
