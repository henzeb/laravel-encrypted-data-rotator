<?php

namespace Henzeb\Rotator\Tests\Stubs\Models;

use Database\Factories\UserFactory;
use Henzeb\Rotator\Attributes\EncryptsData;
use Henzeb\Rotator\Tests\Stubs\MySecuredAttribute;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'custom' => MySecuredAttribute::class,
    ];
    protected $schema = [
        'name' => 'string',
        'string' => 'string',
        'json' => 'string',
        'array' => 'string',
        'collection' => 'string',
        'object' => 'string',
        'custom' => 'string',
        'my_attribute' => 'string'
    ];

    protected $rows = [];

    #[EncryptsData]
    public function myAttribute(): Attribute
    {
        return Attribute::set(
            fn($value) => encrypt($value)
        )->get(fn($value) => decrypt($value));
    }

    public function myNotEncryptedAttribute(): Attribute
    {
        return Attribute::set(
            fn($value) => $value
        )->get(fn($value) => $value);
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
