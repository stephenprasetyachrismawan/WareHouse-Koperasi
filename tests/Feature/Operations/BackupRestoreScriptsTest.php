<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BackupRestoreScriptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_backup_and_restore_drill_preserve_database_and_private_objects(): void
    {
        $root = sys_get_temp_dir().'/warehouse-backup-'.Str::uuid();
        $database = $root.'/source.sqlite';
        $private = $root.'/private';
        $backup = $root.'/backups';
        $restore = $root.'/restore';

        File::makeDirectory($private, 0750, true);
        File::put($private.'/evidence.txt', 'synthetic-evidence');
        $this->runProcess(['sqlite3', $database, 'CREATE TABLE sample (value TEXT); INSERT INTO sample VALUES (\'warehouse-a\');']);

        try {
            $create = $this->runProcess([
                'bash', 'scripts/backup/create-backup.sh',
            ], [
                'BACKUP_ROOT' => $backup,
                'BACKUP_DATABASE_PATH' => $database,
                'BACKUP_PRIVATE_ROOT' => $private,
                'BACKUP_REQUIRE_ENCRYPTION' => 'false',
            ]);

            $this->assertStringContainsString('UNENCRYPTED LOCAL DRILL', $create->getOutput());
            $archive = trim((string) collect(explode("\n", $create->getOutput()))
                ->filter(fn (string $line): bool => str_starts_with($line, 'BACKUP_ARCHIVE='))
                ->map(fn (string $line): string => substr($line, strlen('BACKUP_ARCHIVE=')))
                ->first());
            $this->assertFileExists($archive);

            $restoreResult = $this->runProcess([
                'bash', 'scripts/backup/restore-drill.sh', $archive,
            ], [
                'RESTORE_ROOT' => $restore,
                'RESTORE_CONFIRM' => 'YES',
            ]);

            $this->assertStringContainsString('RESTORE VERIFIED', $restoreResult->getOutput());
            $this->assertFileExists($restore.'/database.sqlite');
            $this->assertSame('warehouse-a', trim($this->runProcess([
                'sqlite3', $restore.'/database.sqlite', 'SELECT value FROM sample;',
            ])->getOutput()));
            $this->assertSame('synthetic-evidence', File::get($restore.'/private-storage/evidence.txt'));
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_production_encryption_guard_rejects_an_unencrypted_backup(): void
    {
        $root = sys_get_temp_dir().'/warehouse-backup-'.Str::uuid();
        File::makeDirectory($root.'/private', 0750, true);
        $this->runProcess(['sqlite3', $root.'/source.sqlite', 'CREATE TABLE sample (value TEXT);']);

        try {
            $process = new Process(['bash', 'scripts/backup/create-backup.sh'], null, [
                'BACKUP_ROOT' => $root.'/backups',
                'BACKUP_DATABASE_PATH' => $root.'/source.sqlite',
                'BACKUP_PRIVATE_ROOT' => $root.'/private',
                'BACKUP_REQUIRE_ENCRYPTION' => 'true',
            ]);
            $process->run();

            $this->assertSame(1, $process->getExitCode());
            $this->assertStringContainsString('encryption is required', $process->getErrorOutput());
        } finally {
            File::deleteDirectory($root);
        }
    }

    /** @param list<string> $command @param array<string, string> $environment */
    private function runProcess(array $command, array $environment = []): Process
    {
        $process = new Process($command, base_path(), $environment + $_ENV);
        $process->mustRun();

        return $process;
    }
}
