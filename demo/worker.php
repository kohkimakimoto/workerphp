<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

//$worker = new \Kohkimakimoto\Worker\Worker(["is_debug" => true]);
$worker = new \Kohkimakimoto\Worker\Worker();
$worker->job("*/2 * * * *", function(){

    $now = new \DateTime;
    echo $now->format('Y-m-d H:i:s');

});

$worker->job("* * * * *", "pwd");;

$worker->start();
