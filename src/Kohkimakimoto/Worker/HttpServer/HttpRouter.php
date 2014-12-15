<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class HttpRouter
{
    protected $routes;

    public function __construct($eventLoop)
    {
        $this->eventLoop = $eventLoop;
        $this->configureRoutes();
    }

    public function configureRoutes()
    {
        $this->routes = new RouteCollection();
        $this->routes->add('index', new Route('/', ['_action' => 'index']));
        $this->routes->add('job', new Route('/{name}', ['_action' => 'job']));
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
