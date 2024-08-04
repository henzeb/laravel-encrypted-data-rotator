<?php

namespace Henzeb\Rotator\Commands;

use Henzeb\Rotator\Concerns\ChecksEnvironment;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'key:rotate')]
class KeyRotateCommand extends KeyGenerateCommand
{
    use ConfirmableTrait,
        ChecksEnvironment;

    const INVALID_ENVIRONMENT = 8;
    const EXECUTION_NOT_ALLOWED = 16;
    const VALIDATION_ERROR = 32;

    protected $signature = 'key:rotate {--f|force}';
    protected $description = 'Rotates the encryption key';

    public function handle(): int
    {
        if (!$this->environmentIsAvailable()) {
            return self::INVALID_ENVIRONMENT;
        }

        if (!$this->confirmToProceed()) {
            return self::EXECUTION_NOT_ALLOWED;
        }

        $oldKey = config('app.key');

        $newKey = $this->generateRandomKey();

        if ($this->updateEnvironmentFileWith($newKey, $oldKey)) {
            $this->components->info('Application key successfully rotated.');
            return 0;
        }

        return self::FAILURE;
    }

    protected function updateEnvironmentFileWith(string $newKey, ?string $oldKey): bool
    {
        $env = File::get($this->laravel->environmentFilePath());

        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY=' . $newKey,
            $input = $env
        );

        if ($replaced === $input || $replaced === null) {
            $this->error('Unable to set application key. No APP_KEY variable was found in the .env file.');

            return false;
        }

        $previousKeys = config('app.previous_keys');

        array_unshift($previousKeys, $oldKey);

        $replaced = preg_replace(
            $this->previousKeysReplacementPattern(),
            'APP_PREVIOUS_KEYS=' . implode(',', $previousKeys),
            $input = $replaced
        );

        if ($replaced === $input || $replaced === null) {
            $this->error('Unable to set previous keys. No APP_PREVIOUS_KEYS variable was found in the .env file.');

            return false;
        }

        File::put($this->laravel->environmentFilePath(), $replaced);

        return true;
    }

    /**
     * laravel doesn't use Crypt, so we cannot mock this. Instead, we use the Crypt facade.
     * @return string
     */
    protected function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(
                Crypt::generateKey(
                    $this->laravel['config']['app.cipher']
                )
            );
    }

    protected function previousKeysReplacementPattern(): string
    {
        $escaped = preg_quote('=' . implode(',', config('app.previous_keys')), '/');

        return "/^APP_PREVIOUS_KEYS{$escaped}/m";
    }
}
