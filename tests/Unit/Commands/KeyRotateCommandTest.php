<?php

namespace Henzeb\Rotator\Tests\Unit\Commands;

use Henzeb\Rotator\Commands\KeyRotateCommand;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\ConfirmPrompt;

it('should test if environment is available', function () {
    $this->artisan('key:rotate', ['--env' => 'doesnotexist'])->assertExitCode(
        KeyRotateCommand::INVALID_ENVIRONMENT
    );
});

it('should ask for confirmation on production', function () {

    Config::set('app.env', 'production');
    $this->app['env'] = 'production';

    ConfirmPrompt::fallbackWhen(true);

    $this->artisan('key:rotate')
        ->expectsQuestion('Are you sure you want to run this command?', false)
        ->assertExitCode(KeyRotateCommand::EXECUTION_NOT_ALLOWED);
});

it('rotation should fail if APP_KEY missing', function () {
    $this->setRandomAppKey();

    File::expects('get')->andReturns(file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate.nokey'));

    File::expects('put')->never();

    $this->artisan('key:rotate')
        ->assertExitCode(KeyRotateCommand::FAILURE);
});

it('rotation should fail if APP_PREVIOUS_KEYS missing', function () {
    Config::set('app.key', 'base64:3+ogn03LYdoGQYNTx3ofk3YxIZA0ST9HblCm+wKabEY=');

    File::expects('get')->andReturns(file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate.nopreviouskeys'));

    File::expects('put')->never();

    $this->artisan('key:rotate')
        ->assertExitCode(KeyRotateCommand::FAILURE);
});

it('rotation should rotate', function () {
    $this->setRandomAppKey();

    Config::set('app.key', 'base64:3+ogn03LYdoGQYNTx3ofk3YxIZA0ST9HblCm+wKabEY=');

    Crypt::expects('generateKey')->with(
        $this->app['config']['app.cipher']
    )->andReturns('generated key');

    File::expects('get')->with($this->app->environmentFilePath())
        ->andReturns(file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate'));

    File::expects('put')->with(
        $this->app->environmentFilePath(),
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate.expected')
    );

    $this->artisan('key:rotate')
        ->assertExitCode(KeyRotateCommand::SUCCESS);
});

it('rotation should rotate and keep older previous keys', function () {
    $this->setRandomAppKey();

    Config::set('app.key', 'base64:3+ogn03LYdoGQYNTx3ofk3YxIZA0ST9HblCm+wKabEY=');
    Config::set('app.previous_keys', ['base64:E2EVW9u6rkdXjwXBHGoJ7Gb80E3PaPcBlfU4vD8PRx0=']);

    Crypt::expects('generateKey')->with(
        $this->app['config']['app.cipher']
    )->andReturns('generated key');

    File::expects('get')->with($this->app->environmentFilePath())
        ->andReturns(file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate.withpreviouskeys'));

    File::expects('put')->with(
        $this->app->environmentFilePath(),
        file_get_contents(__DIR__ . '/../../Stubs/Environments/.env.rotate.expected.withpreviouskeys')
    );

    $this->artisan('key:rotate')
        ->assertExitCode(KeyRotateCommand::SUCCESS);
});


