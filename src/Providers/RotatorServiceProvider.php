<?php

namespace Henzeb\Rotator\Providers;

use Henzeb\Rotator\Commands\KeyCleanupPreviousKeysCommand;
use Henzeb\Rotator\Commands\KeyRotateCommand;
use Henzeb\Rotator\Commands\KeyRotateDataCommand;
use Illuminate\Support\ServiceProvider;

class RotatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rotator.php',
            'rotator'
        );
    }

    public function boot(): void
    {
        $this->commands(
            [
                KeyRotateCommand::class,
                KeyRotateDataCommand::class,
                KeyCleanupPreviousKeysCommand::class
            ]
        );

        $this->publishes(
            [
                __DIR__ . '/../config/rotator.php' => config_path('rotator.php'),
            ],
            'rotator'
        );
    }
}
