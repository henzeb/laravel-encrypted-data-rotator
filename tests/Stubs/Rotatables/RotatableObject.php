<?php

namespace Henzeb\Rotator\Tests\Stubs\Rotatables;

use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Henzeb\Rotator\Tests\Stubs\EncryptableObject;

class RotatableObject implements RotatesEncryptedData
{
    public function __construct(public EncryptableObject $encryptableObject)
    {
    }

    public function rotateEncryptedData(): void
    {
        //
    }
}
