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
        $this->routes->add('index', new Route('/', ['_action' => 'indexAction']));
        $this->routes->add('jobs', new Route('/jobs', ['_action' => 'jobsAction']));
        $this->routes->add('job', new Route('/jobs/{name}', ['_action' => 'jobAction']));
    }

    public function execute($request, $response)
    {
        $context = new RequestContext($request->getPath(), $request->getMethod());
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $parameters = $matcher->match($request->getPath());
            $action = $parameters['_action'];
            call_user_func(array($this, $action), $request, $response, $parameters);
        } catch (ResourceNotFoundException $e) {
            $response->writeHead(404, array('Content-Type' => 'text/plain'));
            $response->end("Not found\n");
            $this->outputAccessLog($request, 404);
        }
    }
}