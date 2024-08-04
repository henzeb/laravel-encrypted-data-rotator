<?php

namespace Database\Factories;

use Henzeb\Rotator\Tests\Stubs\EncryptableObject;
use Henzeb\Rotator\Tests\Stubs\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'string' => $this->faker->text(),
            'json' => [
                'email' => $this->faker->unique()->safeEmail(),
            ],
            'array' => [
                'email' => $this->faker->unique()->safeEmail(),
            ],
            'collection' => [
                'email' => $this->faker->unique()->safeEmail(),
            ],
            'object' => new EncryptableObject(),
        ];
    }
}
