<?php

namespace Henzeb\Rotator\Tests\Stubs\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class User extends Model
{
    use Sushi, HasFactory;

    protected $casts = [
        'string' => 'encrypted',
        'json' => 'encrypted:json',
        'array' => AsEncryptedArrayObject::class,
        'collection' => AsEncryptedCollection::class,
        'object' => 'encrypted:object',
    ];
    protected $schema = [
        'name' => 'string',
        'string' => 'string',
        'json' => 'string',
        'array' => 'string',
        'collection' => 'string',
        'object' => 'string',
    ];

    protected $rows = [];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
