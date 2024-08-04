<?php

namespace Henzeb\Rotator\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ModelEncryptionRotated
{
    use Dispatchable;

    public function __construct(
        public Model $model,
    ) {
    }
}
