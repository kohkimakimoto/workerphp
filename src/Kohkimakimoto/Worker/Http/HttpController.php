<?php
namespace Kohkimakimoto\Worker\Http;

use React\Http\ResponseCodes;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class HttpController
{
    protected $output;

    protected $jobManager;

    protected $routes;

    public function __construct($config, $output, $jobManager)
    {
        $this->config = $config;
        $this->jobManager = $jobManager;
        $this->output = $output;
    }

    public function boot()
    {
        $this->routes = new RouteCollection();
        $this->routes->add('index', new Route('/', ['_action' => 'index']));
    }

    public function execute($request, $response)
    {
        $context = new RequestContext($request->getPath(), $request->getMethod());
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $parameters = $matcher->match($request->getPath());
            $action = $parameters['_action'];
            call_user_func(array($this, $action), $request, $response);
        } catch (ResourceNotFoundException $e) {
            $response->writeHead(404, array('Content-Type' => 'text/plain'));
            $response->end("Not found\n");
            $this->outputAccessLog($request, 404);
        }
    }

    private function outputAccessLog($request, $status)
    {
        if ($status == 200) {
            $color = "blue";
        } else {
            $color = "red";
        }
        $this->output->writeln("<info>HTTP ".$request->getMethod().": </info><comment>".$request->getPath()."</comment> <fg=$color>$status ".ResponseCodes::$statusTexts[$status]."</fg=$color>");
    }

    public function index($request, $response)
    {
        $parameters = [
            "name" => $this->config->getName(),
            "number_of_jobs" => count($this->jobManager->getJobs()),
        ];

        $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
        $response->end(json_encode($parameters));
        $this->outputAccessLog($request, 200);
    }

}
