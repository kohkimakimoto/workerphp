<?php
namespace Kohkimakimoto\Worker\Config;

class Config
{
    const DEFAULT_APP_NAME = 'WorkerPHP';

    protected $name;

    protected $isDebug;

    protected $tmpDir;

    public function __construct($config = array())
    {
        if (isset($config["name"])) {
            $this->name = $config["name"];
        } else {
            $this->name = self::DEFAULT_APP_NAME;
        }

        if (isset($config["debug"]) && $config["debug"]) {
            $this->isDebug = true;
        } else {
            $this->isDebug = false;
        }

        if (isset($config["tmp_dir"]) && $config["tmp_dir"]) {
            $this->tmpDir = $config["tmp_dir"];
        } else {
            $this->tmpDir = sys_get_temp_dir();
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function isDebug()
    {
        return $this->isDebug;
    }

    public function getTmpDir()
    {
        return $this->tmpDir;
    }
}
