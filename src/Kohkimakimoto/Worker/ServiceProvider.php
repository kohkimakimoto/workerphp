<?php
namespace Kohkimakimoto\Worker;

class ServiceProvider
{
    public function register(Worker $worker) {}

    public function start(Worker $worker) {}

    public function shutdown(Worker $worker) {}
}