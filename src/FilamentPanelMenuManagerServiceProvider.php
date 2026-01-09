<?php

namespace Ranjeet\FilamentPanelMenuManager;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Ranjeet\FilamentPanelMenuManager\Commands\FilamentPanelMenuManagerCommand;
use Ranjeet\FilamentPanelMenuManager\Testing\TestsFilamentPanelMenuManager;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPanelMenuManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-panel-menu-manager';

    public static string $viewNamespace = 'filament-panel-menu-manager';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('ranjeet/filament-panel-menu-manager');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-panel-menu-manager/{$file->getFilename()}"),
                ], 'filament-panel-menu-manager-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentPanelMenuManager);

        require_once __DIR__ . '/Helpers/functions.php';
    }

    protected function getAssetPackageName(): ?string
    {
        return 'ranjeet/filament-panel-menu-manager';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('filament-panel-menu-manager', __DIR__ . '/../resources/dist/components/filament-panel-menu-manager.js'),
            /*Css::make('filament-panel-menu-manager-styles', __DIR__ . '/../resources/dist/filament-panel-menu-manager.css'),
            Js::make('filament-panel-menu-manager-scripts', __DIR__ . '/../resources/dist/filament-panel-menu-manager.js'),*/
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentPanelMenuManagerCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_filament_panel_menu_manager_table',
        ];
    }
}
