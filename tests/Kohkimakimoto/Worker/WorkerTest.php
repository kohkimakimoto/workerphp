<?php
namespace Test\Kohkimakimoto\Worker;

use Kohkimakimoto\Worker\Worker;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $worker = new Worker();
        $worker->job("* * * * *", "echo this is test");
    }
}
