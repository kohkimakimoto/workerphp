# WorkerPHP

[![Build Status](https://travis-ci.org/kohkimakimoto/workerphp.svg?branch=master)](https://travis-ci.org/kohkimakimoto/workerphp)

A PHP micro job scheduler framework like cron.

```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

$worker->job("* * * * *", function(){

    // your code.

});

$worker->job("10 * * * *", "echo Hello world");;

$worker->start();
```

***This software is in development stage!***

## Requirements

* PHP5.3 or later
* pcntl Extention

## Installation

```json
{
    "require": {
        "kohkimakimoto/workerphp": "0.*"
    }
}
```

## Usage

## Author

Kohki Makimoto <kohki.makimoto@gmail.com>

## License

MIT license.

