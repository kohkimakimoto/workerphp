<?php
namespace Kohkimakimoto\Worker\HttpServer;

use React\Http\Server as ReactHttpServer;
use React\Socket\Server as ReactSocketServer;
use React\Http\ResponseCodes;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class HttpServer
{
    protected $worker;

    protected $output;

    protected $eventLoop;

    protected $port;

    protected $host;

    protected $http;

    protected $apiKeys = [];

    protected $socket;

    protected $router;

    protected $booted = false;

    public function __construct($worker, $output, $eventLoop, $router)
    {
        $this->worker = $worker;
        $this->output = $output;
        $this->eventLoop = $eventLoop;
        $this->router = $router;
    }

    public function listen($port = 8080, $host = '0.0.0.0')
    {
        $this->port = $port;
        $this->host = $host;
    }

    public function addAPIKey($apiKey, $description = null)
    {
        $this->apiKeys[$apiKey] = $description;
    }

    public function getAPIKeys()
    {
        return $this->apiKeys;
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

        $http->on('request', function ($request, $response) use ($routes) {

            // check api key.
            if (count($this->apiKeys) > 0) {
                $query = $request->getQuery();
                if (!isset($query['apiKey']) || !isset($this->apiKeys[$query['apiKey']])) {
                    $response->writeHead(401, array('Content-Type' => 'text/plain'));
                    $response->end("Unauthorized\n");
                    $this->outputAccessLog($request, 401);
                    return;
                }
            }

            $context = new RequestContext($request->getPath(), $request->getMethod());
            $matcher = new UrlMatcher($routes, $context);
            try {
                $parameters = $matcher->match($request->getPath());
                $action = $parameters['_action'];
                $controller = new HttpController($this->worker, $this, $request, $response);
                call_user_func(array($controller, $action), $parameters);
            } catch (ResourceNotFoundException $e) {
                $response->writeHead(404, array('Content-Type' => 'text/plain'));
                $response->end("Not found\n");
                $this->outputAccessLog($request, 404);
            }

        });

        $this->booted = true;
        $this->output->writeln("<info>Initializing http server:</info> <comment>http://".$this->host.":".$this->port."/</comment>");
    }

    public function outputAccessLog($request, $status)
    {
        if ($status == 200) {
            $color = "blue";
        } else {
            $color = "red";
        }

        $qs = http_build_query($request->getQuery());
        if ($qs) {
            $qs = "?".$qs;
        }

        $this->output->writeln("<info>HTTP ".$request->getMethod().": </info><comment>".$request->getPath().$qs."</comment> <fg=$color>$status ".ResponseCodes::$statusTexts[$status]."</fg=$color>");
    }

    public function shutdown()
    {
        if ($this->booted) {
            $this->socket->shutdown();
        }
    }
}
