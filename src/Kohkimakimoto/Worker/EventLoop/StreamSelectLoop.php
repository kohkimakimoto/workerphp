<?php
namespace Kohkimakimoto\Worker\EventLoop;

// https://github.com/reactphp/react/pull/297

class StreamSelectLoop extends \React\EventLoop\StreamSelectLoop
{
    /**
     * Emulate a stream_select() implementation that does not break when passed
     * empty stream arrays.
     *
     * @param array        &$read   An array of read streams to select upon.
     * @param array        &$write  An array of write streams to select upon.
     * @param integer|null $timeout Activity timeout in microseconds, or null to wait forever.
     *
     * @return integer The total number of streams that are ready for read/write.
     */
    protected function streamSelect(array &$read, array &$write, $timeout)
    {
        if ($read || $write) {
            $except = null;

            // return stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
            return @stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
        }

        usleep($timeout);

        return 0;
    }
}