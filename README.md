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
* [Service Providers](#service-providers)

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

This `$worker->job` method has two arguments. The first argument is name of job. It must be unique in all jobs.
The second argument is an array that has some parameters. `cron_time` is a schedule when to run the job.
It is a "cron expressions" string. `command` is a closure that is executed by the worker.

You can run it. You will get messages like the below.

```
$ php worker.php
Starting WorkerPHP.
Initializing job: hello (job_id: 0)
Successfully booted. Quit working with CONTROL-C.
Running job: hello (pid: 36643) at 2014-12-08 14:56:00
Hello world
Finished job: hello (pid: 36643) at 2014-12-08 14:56:00
Running job: hello (pid: 36646) at 2014-12-08 14:57:00
Hello world
Finished job: hello (pid: 36646) at 2014-12-08 14:57:00
Running job: hello (pid: 36647) at 2014-12-08 14:58:00
Hello world
Finished job: hello (pid: 36647) at 2014-12-08 14:58:00
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
Finished job: uptime (pid: 36650) at 2014-12-04 12:37:00
```


You can set a limit on running processes at the same time. Use `max_processes`.

```php
$worker->job("hello", ['cron_time' => '* * * * *', 'max_processes' => 1, 'command' => function(){
    echo "Hello world\n";
    sleep(70);
;}]);
```

```
$ php worker.php
...
Runs job: hello (pid: 90621) at 2014-12-16 08:03:00
Hello world
Skip the job 'hello' due to limit of max processes: 1 at 2014-12-16 08:04:00
```

### Http Server (Web APIs)

WorkerPHP has a built-in http server. It provides APIs that controls jobs using HTTP requests. Write the following code.

```php
$worker = new \Kohkimakimoto\Worker\Worker();
$worker->httpServer->listen();

// ...

$worker->start();
```

When WorkerPHP starts, It listens port `8080` (default).
You can modify listened port and host.

```php
$worker->httpServer->listen(8888, 'localhost');
```

#### Get a worker info

If you started http server, you can get worker infomation using http request.

```
$ curl -XGET http://localhost:8080/?pretty=1
```

You will get below json.

```
{
    "name": "WorkerPHP",
    "number_of_jobs": 2,
    "jobs": [
        {
            "id": 0,
            "name": "hello",
            "max_processes": 1,
            "last_runtime": "2014-12-15 15:55:38",
            "next_runtime": "2014-12-15 15:56:00",
            "arguments": []
        },
        {
            "id": 1,
            "name": "uptime",
            "max_processes": 1,
            "last_runtime": "2014-12-15 15:55:38",
            "next_runtime": "2014-12-15 15:56:00",
            "arguments": []
        }
    ]
}
```

#### Get a job info

You can get a job info by specifing job name.

```
$ curl -XGET http://localhost:8080/hello?pretty=1
```

You will get below json.

```
{
    "number_of_running_jobs": 0
}
```

#### Run a job

You can run a job using POST request.

```
$ curl -XPOST http://localhost:8080/hello?pretty=1
```

You will get below json.

```
{
    "status": "OK"
}
```

### Service Providers

ServiceProvider allow the user to extend WorkerPHP. Please see the built-in Service Provider `StatsServiceProvider`.



## Author

Kohki Makimoto <kohki.makimoto@gmail.com>

## License

MIT license.

