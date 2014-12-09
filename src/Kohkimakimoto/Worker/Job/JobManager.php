<?php
namespace Kohkimakimoto\Worker\Job;

use Kohkimakimoto\Worker\Worker;

class JobManager
{
    protected $jobs = [];

    protected $config;

    protected $output;

    public function __construct($config, $output)
    {
        $this->config = $config;
        $this->output = $output;
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

    public function boot()
    {
        // All registered jobs is initialized.
        $bootTime = new DateTime();
        foreach ($this->jobs as $job) {
            $this->output->writeln("<info>Initializing job:</info> <comment>".$job->getName()."</comment> (job_id: <comment>".$job->getId()."</comment>)");
            $job->setLastRunTime($bootTime);

            if ($job->hasCronTime()) {
                $this->addJobAsTimer($job);
            }
        }
    }

    protected function addJobAsTimer($job)
    {

    }
}