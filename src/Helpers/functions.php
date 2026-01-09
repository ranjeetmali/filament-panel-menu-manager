<?php

use BezhanSalleh\FilamentShield\Support\Utils;

if (! function_exists('shield_permission')) {
    function shield_permission($resourceClass, $permission)
    {
        // Return null if Shield is not installed
        if (! class_exists(Utils::class)) {
            return null;
        }

        $suffix = \Str::of($resourceClass)
            ->afterLast('Resources\\')
            ->before('Resource')
            ->replace('\\', '')
            ->snake()
            ->replace('_', '::');

        return $permission.'_'.$suffix;
    }
}

if (! function_exists('filament_resources_with_urls')) {
    function filament_resources_with_urls()
    {
        $resources = [];
        foreach (\Filament\Facades\Filament::getCurrentPanel()->getResources() as $resource) {
            // foreach index, create pages
            foreach (['index', 'create'] as $pageType) {
                $nameLabel = $pageType === 'index' ? 'List' : 'Create';
                $permission = shield_permission($resource, $pageType === 'index' ? 'view_any' : 'create');
                $resources[] = [
                    'name' => ucfirst($resource::getModelLabel()).' '.$nameLabel,
                    'class' => $resource,
                    'url' => $resource::getUrl($pageType),
                    'shield_permission' => $permission,
                ];
            }

        }

        return $resources;
    }
}

if (! function_exists('filament_pages_with_urls')) {
    function filament_pages_with_urls()
    {
        $pages = [];
        $excludePages = [
            config('filament-panel-menu-manager.exclude_pages')
        ];
        foreach (\Filament\Facades\Filament::getCurrentPanel()->getPages() as $page) {
            if (in_array($page, $excludePages)) {
                continue;
            }

            // Return null for shield_permission if Shield is not installed
            $permission = null;
            if (class_exists(Utils::class)) {
                $permissionPrefix = Utils::getPagePermissionPrefix();
                $permission = Str::of(class_basename($page))
                    ->prepend(
                        Str::of($permissionPrefix)
                            ->append('_')
                            ->toString()
                    )
                    ->toString();
            }

            $pages[] = [
                'name' => $page::getNavigationLabel(),
                'class' => $page,
                'url' => $page::getUrl(),
                'shield_permission' => $permission,
            ];
        }

        return $pages;
    }
}

if (! function_exists('filament_routes_with_urls')) {
    function filament_routes_with_urls()
    {
        return array_merge(
            filament_resources_with_urls(),
            filament_pages_with_urls()
        );
    }
}
