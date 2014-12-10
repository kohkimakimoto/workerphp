<?php
namespace Kohkimakimoto\Worker;

class Config
{
    const DEFAULT_APP_NAME = 'WorkerPHP';

    protected $name;

    protected $isDebug;

    public function __construct($config)
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
    }

    public function getName()
    {
        return $this->name;
    }

    public function isDebug()
    {
        return $this->isDebug;
    }
}
