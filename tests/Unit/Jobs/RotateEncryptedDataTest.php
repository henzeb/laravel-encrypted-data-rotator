<?php

use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Henzeb\Rotator\Jobs\RotateEncryptedValues;
use Illuminate\Database\Eloquent\Model;

it('should call rotateEncryptedData', function () {

    $mock = Mockery::mock(RotatesEncryptedData::class);
    $mock->expects('rotateEncryptedData')->once();

    $job = new RotateEncryptedValues($mock);

    $job->handle();
});

it('should have a unique value', function () {
    $mock = new class implements RotatesEncryptedData {
        public function rotateEncryptedData(): void
        {
        }
    };

    $job = new RotateEncryptedValues($mock);

    expect($job->uniqueId())->toBe($mock::class);
});

it('should have a unique value with uniqueId', function () {
    $mock = new class implements RotatesEncryptedData {
        public function uniqueId(): string
        {
            return 'myUniqueId';
        }

        public function rotateEncryptedData(): void
        {
        }
    };

    $job = new RotateEncryptedValues($mock);

    expect($job->uniqueId())->toBe($mock::class . 'myUniqueId');
});

it('should use key from model as uniqueId', function () {
    $mock = new class extends Model implements RotatesEncryptedData {
        protected $primaryKey = 'myId';

        public function rotateEncryptedData(): void
        {
        }
    };

    $mock->myId = 9001;

    $job = new RotateEncryptedValues($mock);

    expect($job->uniqueId())->toBe($mock::class . '9001');
});

it('should use uniqueId method on model', function () {
    $mock = new class extends Model implements RotatesEncryptedData {
        protected $primaryKey = 'myId';

        public function rotateEncryptedData(): void
        {
        }

        public function uniqueId(): string
        {
            return 'myUniqueId';
        }
    };

    $mock->myId = 9001;

    $job = new RotateEncryptedValues($mock);

    expect($job->uniqueId())->toBe($mock::class . 'myUniqueId');
});
