<?php
namespace Kohkimakimoto\Worker\Foundation;


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

        $dir = $config->getTmpDir();
        $file = uniqid($job->prefixOfRunFile())
            .spl_object_hash($this);

        $this->runFile = $dir."/".$file;
    }

    public function createRunFileWithPid($pid)
    {
        file_put_contents($this->runFile, $pid);
        $this->pid = $pid;
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

    public function addEntryToJobInfo()
    {
        $path = $this->job->getInfoFilePath();
        if (!file_exists($path)) {
            throw new \RuntimeException("$path not found");
        }

        $fp = fopen($path, "ab+");
        if (flock($fp, LOCK_EX)) {
            $contents = fread($fp, filesize($path));
            $info = json_decode($contents, true);
            if (!$info) {
                $info = ["runtime_jobs" => []];
            }
            $info["runtime_jobs"]["pid-".$this->pid] = ["file" => $this->runFile];
            $contents = json_encode($info, JSON_PRETTY_PRINT);
            ftruncate($fp, 0);
            fwrite($fp, $contents);
        } else {
            throw new \RuntimeException("Coundn't have a lock form $path");
        }

        fclose($fp);
    }

    public function deleteEntryToJobInfo()
    {
        $path = $this->job->getInfoFilePath();
        if (!file_exists($path)) {
            throw new \RuntimeException("$path not found");
        }

        $fp = fopen($path, "ab+");
        if (flock($fp, LOCK_EX)) {
            $contents = fread($fp, filesize($path));
            $info = json_decode($contents, true);
            if (!$info) {
                $info = ["runtime_jobs" => []];
            }
            if (isset($info["runtime_jobs"]["pid-".$this->pid])) {
                unset($info["runtime_jobs"]["pid-".$this->pid]);
                $contents = json_encode($info, JSON_PRETTY_PRINT);
            }
            ftruncate($fp, 0);
            fwrite($fp, $contents);
        } else {
            throw new \RuntimeException("Coundn't have a lock form $path");
        }

        fclose($fp);
    }
}
