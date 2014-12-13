<?php
namespace Kohkimakimoto\Worker\Foundation;

final class Events
{
    const WORKER_STARTED = 'worker.started';

    const JOB_FORKED_PROCESS = 'job.forked_process';

    const WORKER_SHUTTING_DOWN = 'worker.shutting_down';
}
