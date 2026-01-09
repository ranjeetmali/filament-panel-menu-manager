<?php

namespace Ranjeet\FilamentPanelMenuManager\Helpers;

use Filament\Facades\Filament;
use Illuminate\Support\Str;

class RouteDiscovery
{
    /**
     * Check if Filament Shield is installed and enabled
     */
    public static function isShieldAvailable(): bool
    {
        return class_exists(\BezhanSalleh\FilamentShield\Support\Utils::class)
            && config('filament-menu-manager.permissions.shield_integration', true);
    }

    /**
     * Get all Filament routes as options for select fields
     */
    public static function getRouteOptions(): array
    {
        return collect(static::getAllRoutes())
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Get route data by name
     */
    public static function getRouteData(string $name): ?array
    {
        return collect(static::getAllRoutes())->firstWhere('name', $name);
    }

    /**
     * Get all Filament resources with their URLs
     */
    public static function getResources(): array
    {
        $resources = [];

        foreach (Filament::getCurrentPanel()->getResources() as $resource) {
            foreach (['index', 'create'] as $pageType) {
                $nameLabel = $pageType === 'index' ? 'List' : 'Create';
                $permission = static::getShieldPermission($resource, $pageType === 'index' ? 'view_any' : 'create');

                $resources[] = [
                    'name' => ucfirst($resource::getModelLabel()) . ' ' . $nameLabel,
                    'class' => $resource,
                    'url' => $resource::getUrl($pageType),
                    'shield_permission' => $permission,
                ];
            }
        }

        return $resources;
    }

    /**
     * Get all Filament pages with their URLs
     */
    public static function getPages(): array
    {
        $pages = [];
        $excludePages = config('filament-menu-manager.exclude_pages', []);

        foreach (Filament::getCurrentPanel()->getPages() as $page) {
            if (in_array($page, $excludePages)) {
                continue;
            }

            $permission = static::getPagePermission($page);

            $pages[] = [
                'name' => $page::getNavigationLabel(),
                'class' => $page,
                'url' => $page::getUrl(),
                'shield_permission' => $permission,
            ];
        }

        return $pages;
    }

    /**
     * Get all routes (resources + pages)
     */
    public static function getAllRoutes(): array
    {
        return array_merge(
            static::getResources(),
            static::getPages()
        );
    }

    /**
     * Get Shield permission for a resource
     * Returns null if Shield is not available
     */
    protected static function getShieldPermission(string $resourceClass, string $permission): ?string
    {
        if (! static::isShieldAvailable()) {
            return null;
        }

        $suffix = Str::of($resourceClass)
            ->afterLast('Resources\\')
            ->before('Resource')
            ->replace('\\', '')
            ->snake()
            ->replace('_', '::');

        return $permission . '_' . $suffix;
    }

    /**
     * Get Shield permission for a page
     * Returns null if Shield is not available
     */
    protected static function getPagePermission(string $pageClass): ?string
    {
        if (! static::isShieldAvailable()) {
            return null;
        }

        $prefix = \BezhanSalleh\FilamentShield\Support\Utils::getPagePermissionPrefix();

        return Str::of(class_basename($pageClass))
            ->prepend($prefix . '_')
            ->toString();
    }
}
