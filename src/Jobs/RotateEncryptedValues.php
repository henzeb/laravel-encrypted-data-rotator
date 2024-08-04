<?php

namespace Henzeb\Rotator\Jobs;

use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;

class RotateEncryptedValues implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    public function __construct(
        protected readonly RotatesEncryptedData $rotatable
    ) {
    }

    public function uniqueId(): string
    {
        $uniqueId = $this->rotatable::class;

        if (method_exists($this->rotatable, 'uniqueId')) {
            $uniqueId .= $this->rotatable->uniqueId();
        } elseif ($this->rotatable instanceof Model) {
            $uniqueId .= $this->rotatable->getKey();
        }

        return $uniqueId;
    }

    public function handle(): void
    {
        $this->rotatable->rotateEncryptedData();
    }
}
