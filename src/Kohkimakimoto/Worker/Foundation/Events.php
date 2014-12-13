<?php
namespace Kohkimakimoto\Worker\Foundation;

final class Events
{
    const STARTED_WORKER = 'worker.started_worker';

    const FORKED_JOB_PROCESS = 'worker.forked_job_process';

    const SHUTTING_DOWN_WORKER = 'worker.shutting_down_worker';
}
