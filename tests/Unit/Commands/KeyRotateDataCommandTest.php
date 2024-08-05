<?php

namespace Henzeb\Rotator\Tests\Unit\Commands;

use Henzeb\Rotator\Commands\KeyRotateDataCommand;
use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Henzeb\Rotator\Jobs\RotateEncryptedValues;
use Henzeb\Rotator\Jobs\RotateModelsWithEncryptedAttributes;
use Henzeb\Rotator\Tests\Stubs\EncryptableObject;
use Henzeb\Rotator\Tests\Stubs\Models\User;
use Henzeb\Rotator\Tests\Stubs\Rotatables\RotatableObject;
use Illuminate\Support\Facades\Config;
use Laravel\Prompts\ConfirmPrompt;
use Queue;

it('should test if environment is available', function () {
    Queue::fake();
    $this->artisan('key:rotate-data', ['--env' => 'doesnotexist'])->assertExitCode(
        KeyRotateDataCommand::INVALID_ENVIRONMENT
    );

    Queue::assertNothingPushed();

});

it('should ask for confirmation on production', function () {

    Queue::fake();

    Config::set('app.env', 'production');
    $this->app['env'] = 'production';

    ConfirmPrompt::fallbackWhen(true);

    $this->artisan('key:rotate-data')
        ->expectsQuestion('Are you sure you want to run this command?', false)
        ->assertExitCode(KeyRotateDataCommand::EXECUTION_NOT_ALLOWED);

    Queue::assertNothingPushed();
});

it('should dispatch no jobs', function () {

    Queue::fake();

    $this->artisan('key:rotate-data')
        ->assertExitCode(KeyRotateDataCommand::SUCCESS);

    Queue::assertNothingPushed();
});

it('should dispatch jobs for models', function () {

    Queue::fake();

    Config::set('rotator.namespaces', [
        'Henzeb\Rotator\Tests\Stubs\Models'
    ]);


    $this->artisan('key:rotate-data')
        ->assertExitCode(KeyRotateDataCommand::SUCCESS);

    $proxy = new class('test', []) extends RotateModelsWithEncryptedAttributes {
        public function getEncryptedAttributes(RotateModelsWithEncryptedAttributes $job): array
        {
            return $job->attributes;
        }

        public function getModel(RotateModelsWithEncryptedAttributes $job): string
        {
            return $job->model;
        }
    };

    Queue::assertPushed(
        RotateModelsWithEncryptedAttributes::class,
        function (RotateModelsWithEncryptedAttributes $job) use ($proxy) {
            expect($proxy->getEncryptedAttributes($job))->toBe([
                'string',
                'json',
                'array',
                'collection',
                'object',
                'custom'
            ]);
            return $proxy->getModel($job) === User::class;
        }
    );

    Queue::assertCount(1);
});

it('should dispatch jobs for custom objects', function () {

    Queue::fake();

    Config::set('rotator.namespaces', [
        'Henzeb\Rotator\Tests\Stubs\Rotatables'
    ]);


    $this->artisan('key:rotate-data')
        ->assertExitCode(KeyRotateDataCommand::SUCCESS);

    $proxy = new class(
        new RotatableObject(new EncryptableObject())
    ) extends RotateEncryptedValues {
        public function getObject(RotateEncryptedValues $job): RotatesEncryptedData
        {
            return $job->rotatable;
        }
    };

    Queue::assertPushed(
        RotateEncryptedValues::class,
        function (RotateEncryptedValues $job) use ($proxy) {
            return $proxy->getObject($job) instanceof RotatableObject;
        }
    );

    Queue::assertCount(1);
});

it('should dispatch jobs from multiple namespaces', function () {

    Queue::fake();

    Config::set('rotator.namespaces', [
        'Henzeb\Rotator\Tests\Stubs\Models',
        'Henzeb\Rotator\Tests\Stubs\Models\Rotatable'
    ]);

    $this->artisan('key:rotate-data')
        ->assertExitCode(KeyRotateDataCommand::SUCCESS);

    Queue::assertPushed(RotateModelsWithEncryptedAttributes::class);

    expect(Queue::pushed(RotateModelsWithEncryptedAttributes::class)->count())->toBe(2);

    Queue::assertCount(2);
});

it('should dispatch jobs from namespace recursive', function () {

    Queue::fake();

    Config::set('rotator.namespaces', [
        'Henzeb\Rotator\Tests\Stubs\Models\*',
    ]);

    $this->artisan('key:rotate-data')
        ->assertExitCode(KeyRotateDataCommand::SUCCESS);

    Queue::assertPushed(RotateModelsWithEncryptedAttributes::class);

    expect(Queue::pushed(RotateModelsWithEncryptedAttributes::class)->count())->toBe(2);

    Queue::assertCount(2);
});
