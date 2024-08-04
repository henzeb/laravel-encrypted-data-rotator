<?php

namespace Henzeb\Rotator\Tests;


use Henzeb\Rotator\Providers\RotatorServiceProvider;
use Illuminate\Encryption\Encrypter;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RotatorServiceProvider::class
        ];
    }

    protected function setRandomAppKey(): void
    {
        $currentKey = $this->app['config']['app.key'];
        $previousKeys = $this->app['config']['app.previous_keys'];
        array_unshift($previousKeys, $currentKey);

        $this->app['config']['app.previous_keys'] = array_filter($previousKeys);

        $this->app['config']['app.key'] = 'base64:' . base64_encode(
                Encrypter::generateKey($this->app['config']['app.cipher'])
            );
    }
}
