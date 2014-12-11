<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

$worker = new \Kohkimakimoto\Worker\Worker(["debug" => true, "tmp_dir" => __DIR__."/tmp"]);
//$worker = new \Kohkimakimoto\Worker\Worker();
$worker->httpServer->listen();

$worker->job("uptime", ['cron_time' => '* * * * *', 'command' => "uptime"]);

$worker->job("hello", ['cron_time' => '* * * * *', 'max_processes' => 10, 'command' => function(){
    echo "hello\n";
    sleep(10)
;}]);

$worker->start();
