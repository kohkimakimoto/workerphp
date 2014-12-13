<?php
namespace Kohkimakimoto\Worker\Http;

use React\Http\ResponseCodes;
use React\Stream\Stream;
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

    protected $eventLoop;

    public function __construct($eventLoop, $config, $output, $jobManager)
    {
        $this->eventLoop = $eventLoop;
        $this->config = $config;
        $this->jobManager = $jobManager;
        $this->output = $output;
    }

    public function boot()
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

    private function outputAccessLog($request, $status)
    {
        if ($status == 200) {
            $color = "blue";
        } else {
            $color = "red";
        }
        $this->output->writeln("<info>HTTP ".$request->getMethod().": </info><comment>".$request->getPath()."</comment> <fg=$color>$status ".ResponseCodes::$statusTexts[$status]."</fg=$color>");
    }

    public function indexAction($request, $response, $parameters)
    {
        $pretty = false;
        $query = $request->getQuery();
        if (isset($query['pretty']) && $query['pretty'] && $query['pretty'] != "false") {
            $pretty = true;
        }

        $jobs = $this->jobManager->getJobs();

        $jobsList = [];
        foreach ($jobs as $v) {
            $jobsList[] = [
                "id" => $v->getId(),
                "name" => $v->getName(),
            ];
        }
        $contents = [
            "name" => $this->config->getName(),
            "number_of_jobs" => count($jobs),
            "jobs" => $jobsList,
        ];

        $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
        if ($pretty) {
            $output = json_encode($contents, JSON_PRETTY_PRINT);
        } else {
            $output = json_encode($contents);
        }
        $response->end($output);
        $this->outputAccessLog($request, 200);
    }

    public function jobsAction($request, $response, $parameters)
    {
        $pretty = false;
        $query = $request->getQuery();
        if (isset($query['pretty']) && $query['pretty'] && $query['pretty'] != "false") {
            $pretty = true;
        }

        $jobs = $this->jobManager->getJobs();
        $contents = [];

        foreach ($jobs as $job) {
            $contents[] = [
                "id" => $job->getId(),
                "name" => $job->getName(),
            ];
        }

        $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
        if ($pretty) {
            $output = json_encode($contents, JSON_PRETTY_PRINT);
        } else {
            $output = json_encode($contents);
        }
        $response->end($output);
        $this->outputAccessLog($request, 200);
    }

    public function jobAction($request, $response, $parameters)
    {
        $pretty = false;
        $query = $request->getQuery();
        if (isset($query['pretty']) && $query['pretty'] && $query['pretty'] != "false") {
            $pretty = true;
        }

        $name = $parameters["name"];
        $method = strtolower($request->getMethod());
        $job = $this->jobManager->getJob($name);

        if (!$job) {
            $response->writeHead(404, array('Content-Type' => 'text/plain'));
            $response->end("Not found\n");
            $this->outputAccessLog($request, 404);

            return;
        }

        if ($method == 'get') {
            // print job info
            $stream = new Stream(fopen($job->getInfoFilePath(), 'r'), $this->eventLoop);

            $buffer = null;
            $self = $this;

            $stream->on('data', function ($data, $stream) use (&$buffer) {
                $buffer .= $data;
            });

            $stream->on('end', function ($stream) use ($job, $response, $request, $self, &$buffer, $pretty) {
                $info = json_decode($buffer, true);

                $number = 0;
                if (isset($info["runtime_jobs"])) {
                    $number = count($info["runtime_jobs"]);
                }

                $contents = [];
                $contents['id'] = $job->getId();
                $contents['name'] = $job->getName();
                $contents['number_of_running_jobs'] = $number;

                $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
                if ($pretty) {
                    $output = json_encode($contents, JSON_PRETTY_PRINT);
                } else {
                    $output = json_encode($contents);
                }
                $response->end($output);
                $this->outputAccessLog($request, 200);
            });
        } elseif ($method == 'post') {
            // run job
            $this->jobManager->executeJob($job, true);

            $contents = ["OK"];
            $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
            $response->end(json_encode($contents));
            $this->outputAccessLog($request, 200);
        } else {
            $response->writeHead(404, array('Content-Type' => 'text/plain'));
            $response->end("Not found\n");
            $this->outputAccessLog($request, 404);
        }
    }
}
