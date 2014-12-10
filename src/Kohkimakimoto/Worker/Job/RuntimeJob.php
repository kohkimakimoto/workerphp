<?php
namespace Kohkimakimoto\Worker\Job;

use Cron\CronExpression;
use DateTime;

/**
 * RuntimJob
 */
class RuntimeJob
{
    protected $pid;

    protected $job;

    protected $config;

    protected $runFile;

    protected $numberOfProcesses;

    public function __construct($config, $job)
    {
        $this->config = $config;
        $this->job = $job;

        $dir = sys_get_temp_dir();
        $file = $this->config->getName()
            .".job.".$this->job->getName()
            .".".spl_object_hash($this);

        $this->runFile = $dir."/".$file;
    }

    public function createRunFileWithPid($pid)
    {
        file_put_contents($this->runFile, $pid);
    }

    public function removeRunFile()
    {
        if ($this->runFile && file_exists($this->runFile)) {
            unlink($this->runFile);
            $this->runFile = null;
        }
    }

    public function isRunning()
    {
        if (!$this->runFile) {
            return false;
        }

        return file_exists($this->runFile);
    }

    public function getRunFile()
    {
        return $this->runFile;
    }
}
