<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Worker;

class JobManager
{
    protected $jobs = [];

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function register($name, $command)
    {
        // checks if the same name exists.

        if (array_key_exists($name, $this->jobs)) {
            throw new \InvalidArgumentException("'$name' is already registered as a job.");
        }

        $id = count($this->jobs);
        $this->jobs[$name] = new Job($id, $name, $command, $this->config);

        return $this;
    }
}