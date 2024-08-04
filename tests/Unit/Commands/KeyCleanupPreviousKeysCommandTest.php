<?php

namespace Henzeb\Rotator\Tests\Unit\Commands;

use Henzeb\Rotator\Commands\KeyCleanupPreviousKeysCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\ConfirmPrompt;

it('should test if environment is available', function () {
    $this->artisan('key:cleanup-previous-keys', ['--env' => 'doesnotexist'])->assertExitCode(
        KeyCleanupPreviousKeysCommand::class::INVALID_ENVIRONMENT
    );
});

it('should ask for confirmation on production', function () {

    Config::set('app.env', 'production');
    $this->app['env'] = 'production';

    ConfirmPrompt::fallbackWhen(true);

    $this->artisan('key:cleanup-previous-keys')
        ->expectsQuestion('Are you sure you want to run this command?', false)
        ->assertExitCode(KeyCleanupPreviousKeysCommand::EXECUTION_NOT_ALLOWED);
});

it('should throw error on invalid key count', function (mixed $value) {
    $this->artisan('key:cleanup-previous-keys', ['--keep-previous-key-count' => $value])->assertExitCode(
        KeyCleanupPreviousKeysCommand::VALIDATION_ERROR
    );
})->with([
    ['value' => 'not a number'],
    ['value' => -1],
]);

it('should throw error on invalid key count from config', function (mixed $value) {

    Config::set('rotator.keep_previous_key_count', $value);

    $this->artisan('key:cleanup-previous-keys')->assertExitCode(
        KeyCleanupPreviousKeysCommand::VALIDATION_ERROR
    );
})->with([
    ['value' => 'not a number'],
    ['value' => -1],
]);

it('should clean up keys', function () {
    File::expects('get')->andReturn(
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.clean')
    );

    File::expects('put')->with(
        $this->app->environmentFilePath(),
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.clean.expected.2')
    );

    Config::set('app.previous_keys',
        [
            'base64:m56x62DaKvm5YjB73vhpvhUFBoSffpX8jynrStK/lNM=',
            'base64:k5hOSxRXNJNH83hFPPWV+RlkJLf09r3bt7vHEOKKpj4=',
            'base64:JaKt3LYGA/yVQNAfWltA7y3Gax2edNQW0bkIFnBZijc='
        ]
    );

    $this->artisan(
        'key:cleanup-previous-keys'
    )->assertExitCode(
        0
    );
});

it('should clean up keys honoring flag', function (int $count) {
    File::expects('get')->andReturn(
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.clean')
    );

    File::expects('put')->with(
        $this->app->environmentFilePath(),
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.clean.expected.'.$count)
    );

    Config::set('app.previous_keys',
        [
            'base64:m56x62DaKvm5YjB73vhpvhUFBoSffpX8jynrStK/lNM=',
            'base64:k5hOSxRXNJNH83hFPPWV+RlkJLf09r3bt7vHEOKKpj4=',
            'base64:JaKt3LYGA/yVQNAfWltA7y3Gax2edNQW0bkIFnBZijc='
        ]
    );

    $this->artisan(
        'key:cleanup-previous-keys',['--keep-previous-key-count' => $count]
    )->assertExitCode(
        0
    );
})->with([
    ['count' => 0],
    ['count' => 1],
    ['count' => 2],
]);

it('should not clean up keys when not needed', function (int $count) {
    File::expects('get')->never();

    File::expects('put')->never();

    Config::set('app.previous_keys',
        [
            'base64:m56x62DaKvm5YjB73vhpvhUFBoSffpX8jynrStK/lNM=',
            'base64:k5hOSxRXNJNH83hFPPWV+RlkJLf09r3bt7vHEOKKpj4=',
            'base64:JaKt3LYGA/yVQNAfWltA7y3Gax2edNQW0bkIFnBZijc='
        ]
    );

    $this->artisan(
        'key:cleanup-previous-keys',['--keep-previous-key-count' => $count]
    )->assertExitCode(
        0
    );
})->with([
    ['count' => 3],
    ['count' => 4],
]);

it('should fail when no previous_key variable is missing', function () {
    File::expects('get')->andReturn(
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.clean.noprevious')
    );

    File::expects('put')->never();

    Config::set('app.previous_keys',
        [
            'base64:m56x62DaKvm5YjB73vhpvhUFBoSffpX8jynrStK/lNM=',
            'base64:k5hOSxRXNJNH83hFPPWV+RlkJLf09r3bt7vHEOKKpj4=',
            'base64:JaKt3LYGA/yVQNAfWltA7y3Gax2edNQW0bkIFnBZijc='
        ]
    );

    $this->artisan(
        'key:cleanup-previous-keys'
    )->assertExitCode(
        KeyCleanupPreviousKeysCommand::FAILURE
    );
});

