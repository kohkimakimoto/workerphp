# WorkerPHP

[![Build Status](https://travis-ci.org/kohkimakimoto/workerphp.svg?branch=master)](https://travis-ci.org/kohkimakimoto/workerphp)
[![Latest Stable Version](https://poser.pugx.org/kohkimakimoto/workerphp/v/stable.png)](https://packagist.org/packages/kohkimakimoto/workerphp)
[![License](https://poser.pugx.org/kohkimakimoto/workerphp/license.png)](https://packagist.org/packages/kohkimakimoto/workerphp)

A PHP micro job scheduler framework like cron.

```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

// job for every minute.
$worker->job("hello", ['cron_time' => '* * * * *', 'command' => function(){
    echo "Hello world\n";
}]);

// job runs a shell command.
$worker->job("uptime", ['cron_time' => '10 * * * *', 'command' => "uptime"]);

$worker->start();
```

***This software is in development stage! You shouldn't use it in your real products.***

## Requirements

* PHP5.4 or later
* pcntl extention

## Installation

Create `composer.json` for installing via composer. 

```json
{
    "require": {
        "kohkimakimoto/workerphp": "0.*"
    }
}
```

Run composer install command.

```Shell
$ composer install
```

## Usage

* [Bootstrap](#bootstrap)
* [Jobs](#jobs)
* [Http Server (Web APIs)](#http-server-web-apis)

### Bootstrap

To make a job scheduler application like cron, create `worker.php` file (or other name you want).
You need to load composer `autoload.php` file and create an instance of `Kohkimakimoto\Worker\Worker`.

```php
// worker.php
<?php
require_once __DIR__.'/vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

// ... job definitions

$worker->start();
```

Run `php worker.php`. You will get the following messages and the process keep in your system. But it is not useful at this time. Stop the process using CONTROL-C.

```
$ php worker.php
Starting WorkerPHP.
Successfully booted. Quit working with CONTROL-C.
```

Learn about jobs at the next section.

### Jobs

Define a job.

```php
$worker->job("hello", ['cron_time' => '* * * * *', 'command' => function(){
    echo "Hello world\n";
}]);
```

This is a job definition. `$worker->job` method has two arguments. The first is a name of job. It must be unique in all jobs. The second is an array that has some parameters for the job. `cron_time` is a schedule when to run the job. It is a "cron expressions" string. `command` is a closure that is executed by the worker.

You can run it. You will get messages like the below.

```
$ php worker.php
Starting WorkerPHP.
Initializing job: hello (job_id: 0)
Successfully booted. Quit working with CONTROL-C.
Running job: hello (pid: 36643) at 2014-12-08 14:56:00
hello
Running job: hello (pid: 36646) at 2014-12-08 14:57:00
hello
Running job: hello (pid: 36647) at 2014-12-08 14:58:00
hello
```

The job you defined runs every minute.

Also you can define `command` a command string not a closure.

```php
$worker->job("uptime", ['cron_time' => '* * * * *', 'command' => "uptime"]);
```

The worker runs command `uptime` every minute.

```
Running job: uptime (pid: 36650) at 2014-12-04 12:37:00
12:37  up 8 days, 16:06, 6 users, load averages: 1.82 1.74 1.83
Running job: uptime (pid: 36651) at 2014-12-04 12:38:00
12:38  up 8 days, 16:07, 6 users, load averages: 1.68 1.72 1.81
```

### Http Server (Web APIs)

WorkerPHP has a built-in http server. It provides APIs that controls jobs using HTTP requests. Write the following code.

```php
$worker = new \Kohkimakimoto\Worker\Worker();
$worker->httpServer->listen();

// ...

$worker->start();
```

When WorkerPHP starts, It listens port `8080`. You can get infomation using http request.

```
$ curl -XPGET http://localhost:8080/?pretty=1
{
    "name": "WorkerPHP",
    "number_of_jobs": 2,
    "jobs": [
        {
            "id": 0,
            "name": "hello"
            "last_runtime": "2014-12-15 15:55:38",
            "next_runtime": "2014-12-15 15:56:00",
            "arguments": []
        },
        {
            "id": 1,
            "name": "uptime"
            "last_runtime": "2014-12-15 15:55:38",
            "next_runtime": "2014-12-15 15:56:00",
            "arguments": []
        }
    ]
}
```

## Author

Kohki Makimoto <kohki.makimoto@gmail.com>

## License

MIT license.

