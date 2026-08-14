<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class VerifyProductionInfrastructureCommand extends Command
{
    protected $signature = 'ops:verify-production-infrastructure
        {--storage-smoke : Run a synthetic private-storage write/read/delete probe}
        {--confirm-storage-smoke : Confirm that the storage probe may write a synthetic object}';

    protected $description = 'Verify production infrastructure connections without business mutations';

    public function handle(): int
    {
        if ($this->call('ops:validate-production') !== self::SUCCESS) {
            $this->error('CONFIGURATION=FAIL');

            return self::FAILURE;
        }

        $this->line('CONFIGURATION=PASS');

        try {
            DB::connection()->select('select 1');
            $this->line('DATABASE=PASS');
        } catch (Throwable) {
            $this->error('DATABASE=FAIL');

            return self::FAILURE;
        }

        try {
            $ping = Redis::connection('default')->ping();
            if ($ping === false) {
                throw new RuntimeException('Redis did not respond successfully.');
            }

            $this->line('REDIS=PASS');
        } catch (Throwable) {
            $this->error('REDIS=FAIL');

            return self::FAILURE;
        }

        if (! $this->option('storage-smoke')) {
            $this->line('PRIVATE_STORAGE=SKIPPED (use --storage-smoke --confirm-storage-smoke)');

            return self::SUCCESS;
        }

        if (! $this->option('confirm-storage-smoke')) {
            $this->error('PRIVATE_STORAGE=FAIL (explicit --confirm-storage-smoke is required)');

            return self::FAILURE;
        }

        return $this->runStorageProbe();
    }

    private function runStorageProbe(): int
    {
        $disk = null;
        $path = 'phase-6-4e/smoke/'.Str::uuid().'.txt';
        $payload = 'phase-6-4e synthetic infrastructure probe';
        $written = false;

        try {
            $disk = Storage::disk('private');
            $written = $disk->put($path, $payload);
            if (! $written || $disk->get($path) !== $payload) {
                throw new RuntimeException('Private storage probe did not round-trip successfully.');
            }

            if (! $disk->delete($path)) {
                throw new RuntimeException('Private storage probe cleanup failed.');
            }

            $this->line('PRIVATE_STORAGE=PASS');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('PRIVATE_STORAGE=FAIL');

            return self::FAILURE;
        } finally {
            if ($written && $disk !== null) {
                try {
                    $disk->delete($path);
                } catch (Throwable) {
                    // The probe already reports failure; never expose provider details.
                }
            }
        }
    }
}
