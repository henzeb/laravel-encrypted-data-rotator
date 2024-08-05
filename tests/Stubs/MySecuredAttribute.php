<?php

namespace Henzeb\Rotator\Tests\Stubs;

use Henzeb\Rotator\Contracts\CastsEncryptedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MySecuredAttribute implements CastsAttributes, CastsEncryptedAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return decrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return encrypt($value);
    }
}
