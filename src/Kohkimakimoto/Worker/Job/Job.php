<?php
namespace Kohkimakimoto\Worker\Job;

use Cron\CronExpression;
use Symfony\Component\Finder\Finder;
use DateTime;

/**
 * Job
 */
class Job
{
    protected $id;

    protected $name;

    protected $command;

    protected $config;

    protected $lastRunTime;

    protected $nextRunTime;

    protected $lockFile;

    protected $eventLoop;

    protected $cronTime;

    protected $maxProcesses;

    public function __construct($id, $name, $command, $config)
    {
        $this->id = $id;
        $this->name = $name;
        $this->config = $config;

        if (is_string($command)) {
            // Command line string.
            $this->command = $command;
        } elseif ($command instanceof \Closure) {
            // Closure code.
            $this->command = $command;
        } elseif (is_array($command)) {
            // array
            if (isset($command["command"])) {
                $this->command = $command["command"];
            }

            if (isset($command["cron_time"])) {
                $this->cronTime = $command["cron_time"];
            }

            if (isset($command["max_processes"])) {
                $this->maxProcesses = $command["max_processes"];
            } else {
                $this->maxProcesses = false;
            }
        } else {
            throw new \InvalidArgumentException("Unsupported type of 'command'.");
        }

        if ($this->cronTime) {
            $this->cronExpression = CronExpression::factory($this->cronTime);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasCronTime()
    {
        if ($this->cronTime) {
            return true;
        } else {
            return false;
        }
    }

    public function makeRuntimeJob()
    {
        $runtimeJob = new RuntimeJob($this->config, $this);

        return $runtimeJob;
    }

    public function updateNextRunTime()
    {
        // prevent to set same time.
        $lastRunTime = clone $this->lastRunTime;
        $lastRunTime->modify('+5 second');

        $this->nextRunTime = $this->cronExpression->getNextRunDate($lastRunTime);
    }

    public function secondsUntilNextRuntime($from = null)
    {
        if (!$from) {
            $from = new DateTime();
        }

        $ret = $this->nextRunTime->getTimestamp() - $from->getTimestamp();
        if ($ret < 0) {
            $ret = 0;
        }

        return $ret;
    }

    public function getMaxProcesses()
    {
        return $this->maxProcesses;
    }
    public function numberOfRuntimeProcesses()
    {
        $info = $this->getInfo();
        if ($info && isset($info['runtime_jobs'])) {
            return count($info['runtime_jobs']);
        } else {
            return 0;
        }
    }

    public function isLimitOfProcesses()
    {
        if (!$this->maxProcesses) {
            return false;
        }

        return ($this->maxProcesses <= $this->numberOfRuntimeProcesses());
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLastRunTime($lastRunTime)
    {
        $this->lastRunTime = $lastRunTime;
    }

    public function getLastRunTime()
    {
        return $this->lastRunTime;
    }

    public function getNextRunTime()
    {
        return $this->nextRunTime;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function prefixOfRunFile()
    {
        return $this->config->getName().".job.".$this->getName().".";
    }

    public function initInfoFile()
    {
        $file = $this->getInfoFilePath();

        if (file_exists($file)) {
            unlink($file);
        }

        file_put_contents($file, '{}');
    }

    public function getInfoFilePath()
    {
        $dir = $this->config->getTmpDir();

        return $dir."/".$this->config->getName().".".$this->getName().".info.json";
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_runtime' => $this->lastRunTime->format('Y-m-d H:i:s'),
            'next_runtime' => $this->nextRunTime->format('Y-m-d H:i:s'),
        ];
    }

    public function getInfo()
    {
        $path = $this->getInfoFilePath();
        $contents = file_get_contents($path);
        if (!$contents) {
            return null;
        }

        return json_decode($contents, true);
    }

    public function addRuntimeEntryToJobInfo($pid)
    {
        clearstatcache();

        $path = $this->getInfoFilePath();
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
            $info["runtime_jobs"]["job.".$pid] = ["pid" => $pid];
            $contents = json_encode($info, JSON_PRETTY_PRINT);
            ftruncate($fp, 0);
            fwrite($fp, $contents);
        } else {
            throw new \RuntimeException("Coundn't have a lock form $path");
        }
        fclose($fp);
    }

    public function deleteRuntimeEntryToJobInfo($pid)
    {
        clearstatcache();

        $path = $this->getInfoFilePath();
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
            if (isset($info["runtime_jobs"]["job.".$pid])) {
                unset($info["runtime_jobs"]["job.".$pid]);
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
