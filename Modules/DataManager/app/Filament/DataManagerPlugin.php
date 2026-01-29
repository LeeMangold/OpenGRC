<?php

namespace Modules\DataManager\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\DataManager\Filament\Admin\Pages\Export;
use Modules\DataManager\Filament\Admin\Pages\Import;

class DataManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'datamanager';
    }

    public function register(Panel $panel): void
    {
        // Only register if module is enabled
        if (! config('datamanager.enabled', true)) {
            return;
        }

        $panel->pages([
            Export::class,
            Import::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
