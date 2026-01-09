<?php

namespace Ranjeet\FilamentPanelMenuManager\Commands;

use Illuminate\Console\Command;

class FilamentPanelMenuManagerCommand extends Command
{
    public $signature = 'filament-panel-menu-manager';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
