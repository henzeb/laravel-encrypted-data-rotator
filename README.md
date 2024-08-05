# Encrypted data rotator for Laravel 11+

[![Build Status](https://github.com/henzeb/laravel-encrypted-data-rotator/workflows/tests/badge.svg)](https://github.com/henzeb/laravel-encrypted-data-rotator/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-encrypted-data-rotator.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-encrypted-data-rotator)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-encrypted-data-rotator.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-encrypted-data-rotator)
[![License](https://img.shields.io/packagist/l/henzeb/laravel-encrypted-data-rotator)](https://packagist.org/packages/henzeb/laravel-encrypted-data-rotator)

Laravel 11 comes with app key rotation. Sadly, at this point, laravel won't re-encrypt
the data unless a user changes it.

This package allows you to rotate Models by default, but also allows you to rotate
your own custom encrypted data.

## Installation

Just install with the following command.

```bash
composer require henzeb/laravel-encrypted-data-rotator
```

## Usage

### Configuring

You do not need to modify anything when you are just going to rotate the Models.
Everything is done automatically for you. Most is configurable by environment
variables.

#### Publishing the configuration

The configuration file is published with the following command:

```bash
php artisan vendor:publish --tag=rotator
```

### Rotate the key

Laravel does not come with a handy key rotate command, you need to manually save
the key in the `APP_PREVIOUS_KEYS` variable, which you might forget, or simply
make mistakes with.

```bash
php artisan key:rotate
```

You can also select a different environment.

```bash
php artisan key:rotate --env your-env
```

On production, you are being asked if you want to rotate the keys. you can force
this.

```bash
php artisan key:rotate --force
```

### Rotate your data

When you have rotated the key, the encrypted data hasn't changed yet. Laravel will
use the previous keys if the current one cannot decrypt the data. Using the following
the data will also be rotated.

```bash
php artisan key:rotate-data
```

You do not have to do anything on the Models. The Models and their encrypted
attributes are automatically detected.

Note: just like the key rotation command, `key:rotate-data` accepts `env` to
specify an environment and `force` to start rotation without confirmation.

#### rotating custom attribute casts

It is possible to rotate custom casts. Your `CastsAttributes` implementation should
either have `encrypted` in its class name, or implement the
`Henzeb\Rotator\Contracts\CastsEncryptedAttributes` interface.

```php

use Henzeb\Rotator\Contracts\CastsEncryptedAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;


class YourCustomAttribute implements CastsAttributes, CastsEncryptedAttributes
{
    // your implementation
}
```

Note: This also works with `CastsInboundAttributes` implementations

#### rotating custom encrypted data

To rotate encrypted data that is not accessible by models, you can implement the
`rotateEncryptedData` method on the `\Henzeb\Rotator\Contracts\RotatesEncryptedData`
interface. When the default configuration is applied, these implementations are also
automatically detected.

#### customize rotation on Models

The `RotatesEncryptedData` interface can also be applied on Models. These
are `per-record` basis.

#### Events

For each processed Model, a `ModelEncryptionRotated` event is emitted. It
contains the Model for easy access.

Note: For custom implementations, there are no events. You have to implement
them yourself.

### Queue

Everything is done utilizing the queue. By default it uses the default queue and
default connection. You can change that by setting the `ROTATOR_QUEUE`
and `ROTATOR_QUEUE_CONNECTION`, or the corresponding keys in the configuration file.

To avoid out of memory, the amount of jobs in the given queue is limited by the
`ROTATOR_JOB_LIMIT`. Whenever the queue size is too big, the
`RotateModelsWithEncryptedData` job will be placed back on the queue waiting for
the jobs it had dispatched to finish. This is by default 100 jobs.

To avoid heavy load on your database, you can modify the `ROTATOR_CHUNK_SIZE`.
By default, 50 records are pulled from your database and dispatched to the queue.

### Cleaning up previous keys

if you rotate regularly, or when you are confident the keys are no longer needed,
you can cleanup the keys simply by running the following command:

```bash
php artisan key:cleanup-previous-keys
```

Note: just like the other commands, `key:cleanup-previous-keys` accepts `env` to
specify an environment and `force` to clean up without confirmation.

## Testing this package

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email
henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.
