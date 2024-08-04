<?php

use Henzeb\Rotator\Jobs\RotateEncryptedModelAttributes;
use Henzeb\Rotator\Jobs\RotateEncryptedValues;
use Henzeb\Rotator\Jobs\RotateModelsWithEncryptedAttributes;
use Henzeb\Rotator\Tests\Stubs\Models\Rotatable\UserData;
use Henzeb\Rotator\Tests\Stubs\Models\User;

uses()->beforeEach(function () {
    User::truncate();
    UserData::truncate();
});

it('should dispatch for models', function () {

    $this->setRandomAppKey();

    User::factory(1)->create();

    Queue::fake(
        [
            RotateEncryptedModelAttributes::class
        ]
    );

    RotateModelsWithEncryptedAttributes::dispatch(User::class, ['string']);

    Queue::assertPushed(
        RotateEncryptedModelAttributes::class,
        function (RotateEncryptedModelAttributes $job) {
            return $job->uniqueId() === User::class . '1';
        }
    );

    Queue::assertCount(1);
});

it('should dispatch for models on different queue and connection', function () {

    $this->setRandomAppKey();

    User::factory(1)->create();

    Queue::fake(
        [
            RotateEncryptedModelAttributes::class
        ]
    );

    $job = new RotateModelsWithEncryptedAttributes(User::class, ['string']);
    $job->onConnection('redis')->onQueue('randomQueue');

    $job->handle();

    Queue::assertPushed(
        RotateEncryptedModelAttributes::class,
        function (RotateEncryptedModelAttributes $job) {
            return $job->uniqueId() === User::class . '1'
                && $job->connection === 'redis'
                && $job->queue === 'randomQueue';
        }
    );

    Queue::assertCount(1);
});

it('should dispatch for models with custom rotation', function () {

    $this->setRandomAppKey();

    $user = UserData::create([
        'array' => ['myData' => 'data', 'version' => 1]
    ]);

    Queue::fake(
        [
            RotateEncryptedValues::class,
            RotateEncryptedModelAttributes::class,
        ]
    );

    RotateModelsWithEncryptedAttributes::dispatch(UserData::class, ['string']);

    Queue::assertPushed(
        RotateEncryptedValues::class,
        function(RotateEncryptedValues $job){
            return $job->uniqueId() === UserData::class . '1';
        }
    );

    Queue::assertCount(1);
});

it('should dispatch for models with custom rotation on different queue', function () {

    $this->setRandomAppKey();

    $user = UserData::create([
        'array' => ['myData' => 'data', 'version' => 1]
    ]);

    Queue::fake(
        [
            RotateEncryptedValues::class,
            RotateEncryptedModelAttributes::class,
        ]
    );

    $job = new RotateModelsWithEncryptedAttributes(UserData::class, ['string']);
    $job->onConnection('redis')->onQueue('randomQueue');

    $job->handle();

    Queue::assertPushed(
        RotateEncryptedValues::class,
        function(RotateEncryptedValues $job){
            return $job->uniqueId() === UserData::class . '1'
                && $job->connection === 'redis'
                && $job->queue === 'randomQueue';
        }
    );

    Queue::assertCount(1);
});
it('should put job back on the queue', function () {

    $this->setRandomAppKey();

    User::factory(101)->create();

    Queue::fake();

    $job = new RotateModelsWithEncryptedAttributes(User::class, ['string']);

    $job->handle();

    Queue::assertPushed(RotateModelsWithEncryptedAttributes::class);

    Queue::assertCount(101);
});

it('it should respect the job limit and chunksize', function () {

    $this->setRandomAppKey();

    Config::set('rotator.chunk_size', 1);

    Config::set('rotator.job_limit', 2);

    User::factory(3)->create();

    Queue::fake();

    $job = new RotateModelsWithEncryptedAttributes(User::class, ['string']);

    $job->handle();

    Queue::assertPushed(RotateModelsWithEncryptedAttributes::class);

    Queue::assertCount(3); //two times RotateEncryptedAttributeForModel + RotateModelsWithEncryptedAttributes
});
