<?php
namespace Kohkimakimoto\Worker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * WorerCommand
 */
class WorkerCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // worker object
        $worker = $this->getApplication();

        return $worker->doStart($input, $output);
    }
}
