<?php

namespace Henzeb\Rotator\Commands;

use HaydenPierce\ClassFinder\ClassFinder;
use Henzeb\Rotator\Concerns\ChecksEnvironment;
use Henzeb\Rotator\Contracts\CastsEncryptedAttributes;
use Henzeb\Rotator\Contracts\RotatesEncryptedData;
use Henzeb\Rotator\Jobs\RotateEncryptedValues;
use Henzeb\Rotator\Jobs\RotateModelsWithEncryptedAttributes;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class KeyRotateDataCommand extends Command
{
    use ConfirmableTrait,
        ChecksEnvironment;

    const INVALID_ENVIRONMENT = 8;
    const EXECUTION_NOT_ALLOWED = 16;
    const VALIDATION_ERROR = 32;

    protected $signature = 'key:rotate-data {--force}';

    protected $description = 'Rotates the encrypted data';

    public function handle(): int
    {
        if (!$this->environmentIsAvailable()) {
            return self::INVALID_ENVIRONMENT;
        }

        if (!$this->confirmToProceed()) {
            return self::EXECUTION_NOT_ALLOWED;
        }

        $classes = $this->collectRotatableObjects();

        $classes->each(
            function (string $class) {

                match (true) {
                    ($encryptedAttributes = $this->getEncryptedAttributesWhenModel($class)) != false
                    => $this->dispatchModels($class, $encryptedAttributes),
                    $this->rotatesEncryptedValues($class) => $this->dispatchRotatesEncryptedValues($class),
                    default => null
                };
            }
        );

        return 0;
    }

    private function collectRotatableObjects(): Collection
    {
        return collect(
            config('rotator.namespaces', [])
        )->map(
            function (string $namespace) {
                if (str_ends_with($namespace, '*')) {
                    $namespace = rtrim($namespace, '*');
                    $mode = ClassFinder::RECURSIVE_MODE;
                }

                return ClassFinder::getClassesInNamespace(
                    $namespace,
                    $mode ?? ClassFinder::STANDARD_MODE
                );
            }
        )->add(
            config('rotator.class_paths', [])
        )->collapse()
            ->unique()
            ->filter(
                function (string $class) {
                    return is_subclass_of($class, Model::class)
                        || is_subclass_of($class, RotatesEncryptedData::class);
                }
            );
    }

    private function getEncryptedAttributesWhenModel(string $class): bool|array
    {
        if (!is_subclass_of($class, Model::class)) {
            return false;
        }

        return collect(
            (new $class)->getCasts()
        )->filter(
            fn(string $castType) => str_contains(
                    strtolower(class_basename($castType)),
                    'encrypted'
                ) || (
                    class_exists($castType)
                    && is_subclass_of(
                        $castType,
                        CastsEncryptedAttributes::class
                    )
                )
        )->keys()
            ->toArray();
    }

    /**
     * @param string $class
     * @param bool|array $encryptedAttributes
     * @return void
     */
    private function dispatchModels(string $class, bool|array $encryptedAttributes): void
    {
        RotateModelsWithEncryptedAttributes::dispatch(
            $class,
            $encryptedAttributes
        )->onConnection(
            config('rotator.connection')
        )->onQueue(
            config('rotator.queue')
        );
    }

    /**
     * @param string|RotatesEncryptedData $class
     * @return void
     */
    private function dispatchRotatesEncryptedValues(string|RotatesEncryptedData $class): void
    {
        RotateEncryptedValues::dispatch(resolve($class))
            ->onConnection(
                config('rotator.connection')
            )->onQueue(
                config('rotator.queue')
            );
    }

    /**
     * @param string $class
     * @return bool
     */
    private function rotatesEncryptedValues(string $class): bool
    {
        return !is_subclass_of($class, Model::class)
            && is_subclass_of($class, RotatesEncryptedData::class);
    }
}
