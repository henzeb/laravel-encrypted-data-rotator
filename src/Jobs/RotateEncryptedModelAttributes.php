<?php

namespace Henzeb\Rotator\Jobs;

use Henzeb\Rotator\Events\ModelEncryptionRotated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;

class RotateEncryptedModelAttributes implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    /**
     * @param class-string<Model> $model
     * @param array $encryptedAttributes
     * @param mixed $key
     */
    public function __construct(
        protected readonly string $model,
        protected readonly array $encryptedAttributes,
        protected readonly mixed $key
    ) {
    }

    public function uniqueId(): string
    {
        return $this->model . $this->key;
    }

    public function handle(): void
    {
        /**
         * we are loading the model here again, to make sure we use the latest data.
         *
         * @var Model $model
         */
        $model = $this->model::find($this->key);

        if ($model) {

            /**
             * getting the current values from the model
             */
            $decryptedAttributes = $model->only($this->encryptedAttributes);

            /**
             * tricking the Model by letting it think the original value is changed
             */
            $model->forceFill(
                array_map(fn() => null, $model->only($this->encryptedAttributes))
            );

            $model->syncOriginalAttributes($this->encryptedAttributes);

            /**
             * now we update the values
             */

            $model->forceFill(
                $decryptedAttributes
            )->saveQuietly();

            ModelEncryptionRotated::dispatch($model);
        }
    }
}
