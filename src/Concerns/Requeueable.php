<?php

namespace Henzeb\Rotator\Concerns;

trait Requeueable
{
    /**
     * places it back on the queue without using retries
     * And keeps the changes.
     */
    public function backOnTheQueue(): void
    {
        /**
         * turns the job into a fresh 'new' job
         */
        $this->delete();
        $this->job = null;
        $this->delay = 0;

        $job = clone $this;

        dispatch($job)
            ->onQueue($this->queue)
            ->onConnection($this->connection);
    }
}
