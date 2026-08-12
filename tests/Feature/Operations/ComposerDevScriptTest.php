<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Tests\TestCase;

class ComposerDevScriptTest extends TestCase
{
    public function test_composer_dev_builds_frontend_before_starting_laravel_dev_processes(): void
    {
        /** @var array{scripts: array{dev: list<string>}} $composerManifest */
        $composerManifest = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $devScripts = $composerManifest['scripts']['dev'];

        $this->assertSame('npm run build', $devScripts[0]);
        $this->assertSame('@php artisan dev', $devScripts[2]);
    }
}
