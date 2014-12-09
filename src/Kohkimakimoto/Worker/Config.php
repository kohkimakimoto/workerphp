<?php
namespace Kohkimakimoto\Worker;

class Config
{
    const DEFAULT_APP_NAME = 'WorkerPHP';

    public $name;

    public $isDebug;

    public function __construct($config)
    {
        if (isset($config["name"])) {
            $this->name = $config["name"];
        } else {
            $this->name = self::DEFAULT_APP_NAME;
        }

        if (isset($config["is_debug"]) && $config["is_debug"]) {
            $this->isDebug = true;
        } else {
            $this->isDebug = false;
        }
    }
}