<?php
namespace Kohkimakimoto\Worker\HttpServer;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use React\Http\ResponseCodes;
use React\Stream\Stream;

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