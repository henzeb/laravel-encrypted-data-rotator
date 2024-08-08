<?php

use Henzeb\Rotator\Events\ModelEncryptionRotated;
use Henzeb\Rotator\Jobs\RotateEncryptedModelAttributes;
use Henzeb\Rotator\Tests\Stubs\Models\User;
use Illuminate\Support\Facades\Event;

uses()->beforeEach(function () {
    User::truncate();
});

it('should have uniqueId', function() {
    $job = new RotateEncryptedModelAttributes(
        'user',
        [],
        9005
    );

    expect($job->uniqueId())->toBe('user9005');
});

it('rotates attributes', function (array $attributes) {

    $this->setRandomAppKey();

    $user = User::factory()->create();

    $original = $user->getAttributes();

    $this->setRandomAppKey();

    $job = new RotateEncryptedModelAttributes(
        User::class,
        $attributes,
        $user->id
    );

    $job->handle();

    $user->refresh();

    $testableAttributes = [
        'string',
        'array',
        'json',
        'collection',
        'object',
        'custom',
        'my_attribute'
    ];

    foreach ($testableAttributes as $testableAttribute) {
        if (in_array($testableAttribute, $attributes)) {
            expect($original[$testableAttribute])->not()->toBe($user->getRawOriginal($testableAttribute));
        } else {
            expect($original[$testableAttribute])->toBe($user->getRawOriginal($testableAttribute));
        }
    }

})->with([
    ['attributes' => ['string']],
    ['attributes' => ['array']],
    ['attributes' => ['json']],
    ['attributes' => ['collection']],
    ['attributes' => ['object']],
    ['attributes' => ['custom']],
    ['attributes' => ['my_attribute']],
    'all' => ['attributes' => [
        'string',
        'array',
        'json',
        'collection',
        'object',
        'custom',
        'my_attribute'
    ]],
]);

it('should emit event when rotated attributes',
    function () {

        $this->setRandomAppKey();

        Event::fake();
        $user = User::factory()->create();

        $this->setRandomAppKey();

        $job = new RotateEncryptedModelAttributes($user::class, ['string'], $user->id);

        $job->handle();

        $user->refresh();

        Event::assertDispatched(
            ModelEncryptionRotated::class,
            function (ModelEncryptionRotated $event) use ($user) {
                return $event->model instanceof User
                    && $event->model->id === $user->id;
            }
        );
    }
);

it('should handle missing models', function(){

    Event::fake();

    $job = new RotateEncryptedModelAttributes(User::class, [], 9001);

    $job->handle();

    Event::assertNotDispatched(ModelEncryptionRotated::class);

    expect(User::count())->toBe(0);

});
