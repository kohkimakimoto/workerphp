# WorkerPHP

[![Build Status](https://travis-ci.org/kohkimakimoto/workerphp.svg?branch=master)](https://travis-ci.org/kohkimakimoto/workerphp)

A PHP micro job scheduler framework like cron.

```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

// job for every minute.
$worker->job("* * * * *", function(){

    // your code.

});

// job runs command.
$worker->job("10 * * * *", "echo Hello world");;

$worker->start();
```

***This software is in development stage!***

## Requirements

* PHP5.3 or later
* pcntl extention

## Installation

```json
{
    "require": {
        "kohkimakimoto/workerphp": "0.*"
    }
}
```

## Usage

To make a job scheduler application like cron, create `worker.php` file (or other name you want).
You need to load composer `autoload.php` file and create an instance of `Kohkimakimoto\Worker\Worker`.

```php
// worker.php
<?php
require_once __DIR__.'/vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

$worker->start();
```

Run `php worker.php`. You will get the following messages and the process keep in your system. But it is not useful at this time. Stop the process using CONTROL-C.

```
$ php worker.php
Starting WorkerPHP.
Successfully booted. Quit working with CONTROL-C.
```

Define a job before line of `$worker->start()`.

```php
$worker->job("* * * * *", function(){
    echo "Hello world\n";
});
```

This is a job definition. `$worker->job` method has two arguments. The first is a schedule when to run the job. It is a "cron expressions" string.
The second is a closure that is code executed by the worker.

You can run it. You will get messages like the below.

```
$ php worker.php
Starting WorkerPHP.
Initializing a job. (job_id: 0)
Successfully booted. Quit working with CONTROL-C.
Running a job. (job_id: 0) at 2014-12-04 20:30:00
Hello world
Running a job. (job_id: 0) at 2014-12-04 20:31:00
Hello world
Running a job. (job_id: 0) at 2014-12-04 20:32:00
Hello world
```

The job you defined runs every minute.

Also you can define a job with a command string not a closure.

```php
$worker->job("* * * * *", "uptime");
```

The worker runs command `uptime` every minute.

```
Running a job. (job_id: 0) at 2014-12-04 12:37:00
12:37  up 8 days, 16:06, 6 users, load averages: 1.82 1.74 1.83
Running a job. (job_id: 0) at 2014-12-04 12:38:00
12:38  up 8 days, 16:07, 6 users, load averages: 1.68 1.72 1.81
```

## Author

Kohki Makimoto <kohki.makimoto@gmail.com>

## License

MIT license.

