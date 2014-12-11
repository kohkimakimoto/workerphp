<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

$worker = new \Kohkimakimoto\Worker\Worker(["debug" => true]);
//$worker = new \Kohkimakimoto\Worker\Worker();

$worker->httpServer("8888", "localhost");

$worker->job("uptime", ['cron_time' => '* * * * *', 'command' => "uptime"]);
$worker->job("hello", ['cron_time' => '* * * * *', 'max_processes' => 1, 'command' => function(){
    echo "hello\n";
}]);

$worker->start();
