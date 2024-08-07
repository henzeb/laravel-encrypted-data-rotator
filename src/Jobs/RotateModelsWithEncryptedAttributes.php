<?php

namespace Henzeb\Rotator\Jobs;

use Henzeb\Rotator\Concerns\Requeueable;
use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;

class RotateModelsWithEncryptedAttributes implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        Requeueable;

    private int $processedModels = 0;

    /**
     * @param class-string<Model|RotatesEncryptedData> $model
     */
    public function __construct(
        protected readonly string $model,
        protected readonly array $attributes,
    ) {
        $this->queue = config('rotator.queue');
        $this->connection = config('rotator.connection');
    }

    public function handle(): void
    {
        $chunkSize = config('rotator.chunk_size', 50);
        $jobLimit = config('rotator.job_limit', 100);
        $queue = config('rotator.queue');

        $retrievedModels = $chunkSize;

        while ($chunkSize === $retrievedModels
            && Queue::size($queue) < $jobLimit
        ) {
            $this->model::limit($chunkSize)
                ->skip($this->processedModels)
                ->get()
                ->tap(fn() => $this->processedModels += $chunkSize)
                ->tap(function (Collection $collection) use (&$retrievedModels) {
                    $retrievedModels = $collection->count();
                })
                ->each(function (Model $model) {
                    if ($model instanceof RotatesEncryptedData) {
                        RotateEncryptedValues::dispatch($model)
                            ->onConnection($this->connection)
                            ->onQueue($this->queue);
                        return;
                    }

                    RotateEncryptedModelAttributes::dispatch(
                        $this->model,
                        $this->attributes,
                        $model->getKey()
                    )->onConnection($this->connection)
                        ->onQueue($this->queue);
                });
        }

        /**
         * we are done
         */
        if ($chunkSize !== $retrievedModels) {
            return;
        }

        /**
         * We are not done yet, push back onto the queue
         */
        $this->backOnTheQueue();
    }
}
