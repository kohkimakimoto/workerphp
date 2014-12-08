<?php
require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

//$worker = new \Kohkimakimoto\Worker\Worker(["is_debug" => true]);
$worker = new \Kohkimakimoto\Worker\Worker();
$worker->httpServer(":8888");

/*
$worker->job("* * * * *", function(){
    echo "Hello world\n";
});

$worker->job("* * * * *", "uptime");;
*/

$worker->job("hello1", function(){


});

$worker->job("hello2", ['cronTime' => '* * * * *', 'onTick' => function(){

    echo "hello\n";

}]);


$worker->start();
