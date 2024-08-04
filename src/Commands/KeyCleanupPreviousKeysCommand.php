<?php

namespace Henzeb\Rotator\Commands;

use Henzeb\Rotator\Concerns\ChecksEnvironment;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class KeyCleanupPreviousKeysCommand extends Command
{
    use ConfirmableTrait,
        ChecksEnvironment;

    const INVALID_ENVIRONMENT = 8;
    const EXECUTION_NOT_ALLOWED = 16;
    const VALIDATION_ERROR = 32;

    protected $signature = 'key:cleanup-previous-keys {--k|keep-previous-key-count= : Keep at least last n keys}';

    protected $description = 'Cleanup old app keys';

    public function handle(): int
    {
        if (!$this->environmentIsAvailable()) {
            return self::INVALID_ENVIRONMENT;
        }

        if (!$this->confirmToProceed()) {
            return self::EXECUTION_NOT_ALLOWED;
        }

        $keepPreviousKeyCount = $this->option('keep-previous-key-count')
            ?? config('rotator.keep_previous_key_count', 1);

        if (!is_numeric($keepPreviousKeyCount) || $keepPreviousKeyCount < 0) {
            $this->outputComponents()->error('The key count must be an positive integer.');
            return self::VALIDATION_ERROR;
        }

        $keepPreviousKeyCount = (int)$keepPreviousKeyCount;

        $previousKeys = collect(config('app.previous_keys'));

        $newPreviousKeys = $previousKeys->take($keepPreviousKeyCount);

        if ($previousKeys->count() === $newPreviousKeys->count()) {
            $this->info('No need to clean up previous app keys');
            return self::SUCCESS;
        }

        if ($this->updateEnvironmentFile($newPreviousKeys)) {
            $this->outputComponents()->info('Previous app keys were truncated.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function updateEnvironmentFile(Collection $newPreviousKeys): bool
    {
        $env = File::get($this->laravel->environmentFilePath());

        $replaced = preg_replace(
            $this->previousKeysReplacementPattern(),
            'APP_PREVIOUS_KEYS=' . $newPreviousKeys->join(','),
            $input = $env
        );

        if ($replaced === $input || $replaced === null) {
            $this->warn('Previous app keys were not truncated.');
            return false;
        }

        File::put($this->laravel->environmentFilePath(), $replaced);

        return true;
    }

    protected function previousKeysReplacementPattern(): string
    {
        $escaped = preg_quote('=' . implode(',', config('app.previous_keys')), '/');

        return "/^APP_PREVIOUS_KEYS{$escaped}/m";
    }
}
