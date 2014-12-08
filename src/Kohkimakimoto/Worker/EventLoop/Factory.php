<?php
namespace Kohkimakimoto\Worker\EventLoop;

// https://github.com/reactphp/react/pull/297

class Factory extends \React\EventLoop\Factory
{
    public static function create()
    {
        $eventLoop = parent::create();
        if ($eventLoop instanceof \React\EventLoop\StreamSelectLoop) {
            return new StreamSelectLoop();
        }
    }
}
