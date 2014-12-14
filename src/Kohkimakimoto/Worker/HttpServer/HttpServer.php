<?php
namespace Kohkimakimoto\Worker\HttpServer;

use React\Http\Server as ReactHttpServer;
use React\Socket\Server as ReactSocketServer;
use React\Http\ResponseCodes;
use React\Stream\Stream;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class HttpServer
{
    protected $output;

    protected $eventLoop;

    protected $port;

    protected $host;

    protected $http;

    protected $socket;

    protected $router;

    protected $booted = false;

    public function __construct($output, $eventLoop, $router)
    {
        $this->output = $output;
        $this->eventLoop = $eventLoop;
        $this->router = $router;
    }

    public function listen($port = 8080, $host = '0.0.0.0')
    {
        $this->port = $port;
        $this->host = $host;
    }

    public function boot()
    {
        if (!$this->port) {
            // The server doesn't run.
            return;
        }

        $this->socket = new ReactSocketServer($this->eventLoop);
        $http = new ReactHttpServer($this->socket);
        $this->socket->listen($this->port, $this->host);

        $routes = $this->router->getRoutes();

        $http->on('request', function ($request, $response) use ($routes){

            $context = new RequestContext($request->getPath(), $request->getMethod());
            $matcher = new UrlMatcher($routes, $context);
            try {
                $parameters = $matcher->match($request->getPath());
                $action = $parameters['_action'];
                print_r($parameters);
                // call_user_func(array($this, $action), $request, $response, $parameters);
            } catch (ResourceNotFoundException $e) {
                $response->writeHead(404, array('Content-Type' => 'text/plain'));
                $response->end("Not found\n");
                $this->outputAccessLog($request, 404);
            }

        });

        $this->booted = true;
        $this->output->writeln("<info>Initializing http server:</info> <comment>http://".$this->host.":".$this->port."/</comment>");
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

    public function shutdown()
    {
        if ($this->booted) {
            $this->socket->shutdown();
        }
    }
}
