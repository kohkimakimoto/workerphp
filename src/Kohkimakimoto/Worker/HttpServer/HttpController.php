<?php
namespace Kohkimakimoto\Worker\HttpServer;

use React\Stream\Stream;

class HttpController
{
    protected $worker;

    protected $server;

    protected $request;

    protected $response;

    public function __construct($worker, $server, $request, $response)
    {
        $this->worker = $worker;
        $this->server = $server;
        $this->request = $request;
        $this->response = $response;
    }

    public function index($parameters)
    {
        $pretty = $this->getQueryParameter("pretty", false);
        if ($pretty) {
            $pretty = true;
        }

        $jobs = $this->worker->job->getJobs();

        $jobsList = [];
        foreach ($jobs as $v) {
            $jobsList[] = $v->toArray();
        }

        $contents = [
            "name" => $this->worker->config->getName(),
            "number_of_jobs" => count($jobs),
            "jobs" => $jobsList,
        ];

        if ($pretty) {
            $output = json_encode($contents, JSON_PRETTY_PRINT);
        } else {
            $output = json_encode($contents);
        }

        $this->response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
        $this->response->end($output);
        $this->server->outputAccessLog($this->request, 200);
    }

    public function job($parameters)
    {
        $pretty = $this->getQueryParameter("pretty", false);
        if ($pretty) {
            $pretty = true;
        }

        $name = $parameters['name'];
        $method = strtolower($this->request->getMethod());
        $job = $this->worker->job->getJob($name);

        if (!$job) {
            $this->response->writeHead(404, array('Content-Type' => 'text/plain'));
            $this->response->end("Not found\n");
            $this->server->outputAccessLog($this->request, 404);

            return;
        }

        if ($method == 'get') {
            $buffer = null;
            $self = $this;
            $response = $this->response;
            $request = $this->request;

            $stream = new Stream(fopen($job->getInfoFilePath(), 'r'), $this->worker->eventLoop);
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
                $contents['number_of_running_jobs'] = $number;

                $response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
                if ($pretty) {
                    $output = json_encode($contents, JSON_PRETTY_PRINT);
                } else {
                    $output = json_encode($contents);
                }
                $response->end($output);
                $self->server->outputAccessLog($request, 200);
            });
        } elseif ($method == 'post') {
            $query = $this->request->getQuery();
            $this->worker->job->executeJob($job, true, $query);

            $contents = ["status" => "OK"];
            $this->response->writeHead(200, array('Content-Type' => 'application/json; charset=utf-8'));
            if ($pretty) {
                $output = json_encode($contents, JSON_PRETTY_PRINT);
            } else {
                $output = json_encode($contents);
            }

            $this->response->end($output);
            $this->server->outputAccessLog($this->request, 200);
        } else {
            $this->response->writeHead(400, array('Content-Type' => 'text/plain'));
            $this->response->end("Bad Request\n");
            $this->server->outputAccessLog($this->request, 400);
        }
    }

    protected function getQueryParameter($key, $default = null)
    {
        $query = $this->request->getQuery();

        return isset($query[$key]) ? $query[$key] : $default;
    }
}
