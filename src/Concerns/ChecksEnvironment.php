<?php

namespace Henzeb\Rotator\Concerns;

trait ChecksEnvironment
{
    protected function environmentIsAvailable(): bool
    {
        if ($this->option('env') === null || $this->option('env') === env('APP_ENV')) {
            return true;
        }

        $this->outputComponents()->error('Invalid environment specified.');

        return false;
    }
}
