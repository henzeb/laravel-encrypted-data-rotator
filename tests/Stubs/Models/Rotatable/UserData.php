<?php

namespace Henzeb\Rotator\Tests\Stubs\Models\Rotatable;

use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class UserData extends Model implements RotatesEncryptedData
{
    use Sushi;

    protected $casts = [
        'array' => 'encrypted:array',
    ];
    protected $schema = [
        'array' => 'string',
    ];

    protected $fillable = ['array'];

    protected $rows = [];

    public function rotateEncryptedData(): void
    {
        $array = $this->array;

        $array['version'] += 1;
        $this->array = $array;
        $this->save();
    }
}
