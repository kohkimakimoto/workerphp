<?php
namespace Test\Kohkimakimoto\Worker;

use Kohkimakimoto\Worker\Worker;
use Kohkimakimoto\Worker\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $job = new Job(0, "0 * * * *", function(){});
        $job->init(new Worker());

        $this->assertTrue($job->getLastRunTime() instanceof \DateTime);
    }
}
