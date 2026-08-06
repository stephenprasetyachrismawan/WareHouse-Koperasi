<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('warehouse:version')]
#[Description("Display the application's version")]
class WarehouseVersionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $this->line($composer['version'] ?? 'unknown');

        return self::SUCCESS;
    }
}
