<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

use \Kohkimakimoto\Worker\Worker;

$worker = new Worker(["debug" => true, "tmp_dir" => __DIR__."/tmp"]);
//$worker = new \Kohkimakimoto\Worker\Worker();
$worker->httpServer->listen();
$worker->stats->on(2);

//$worker->job("uptime", ['cron_time' => '* * * * *', 'command' => "uptime"]);

$worker->job("hello", ['cron_time' => '* * * * *', 'max_processes' => 1, 'command' => function(Worker $worker, $foo){
    echo "hello\n";
    echo "foo=$foo\n";
;}]);

$worker->start();
