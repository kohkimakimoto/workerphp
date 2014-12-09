<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

//$worker = new \Kohkimakimoto\Worker\Worker(["is_debug" => true]);
$worker = new \Kohkimakimoto\Worker\Worker();

$worker->httpServer("8888", "localhost");


$worker->job("uptime", ['cronTime' => '* * * * *', 'onTick' => "uptime"]);
$worker->job("hello", ['cronTime' => '* * * * *', 'onTick' => function(){

    echo "hello\n";

}]);

$worker->start();
