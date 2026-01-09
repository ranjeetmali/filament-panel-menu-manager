<?php

namespace Ranjeet\FilamentPanelMenuManager\Helpers;

use Ranjeet\FilamentPanelMenuManager\FilamentPanelMenuManagerPlugin;
use Ranjeet\FilamentPanelMenuManager\Models\Menu;

class DefaultMenuHelper
{
    /**
     * Get the default menu structure from current resources and pages
     */
    public static function getDefaultStructure(): array
    {
        $plugin = FilamentPanelMenuManagerPlugin::get();

        if ($structure = $plugin->getDefaultMenuStructure()) {
            return $structure;
        }

        $structure = [];
        $sort = 1;

        // Add pages first (Dashboard, etc.)
        foreach (RouteDiscovery::getPages() as $page) {
            $structure[] = [
                'label' => $page['name'],
                'icon' => 'heroicon-o-document',
                'sort' => $sort++,
                'reference' => $page,
                'type' => 'route',
            ];
        }

        // Add resources (List pages only to avoid duplicates)
        foreach (RouteDiscovery::getResources() as $resource) {
            // Only include index/list pages, skip create pages
            if (! str_ends_with($resource['name'], ' List')) {
                continue;
            }

            $structure[] = [
                'label' => str_replace(' List', '', $resource['name']),
                'icon' => 'heroicon-o-rectangle-stack',
                'sort' => $sort++,
                'reference' => $resource,
                'type' => 'route',
            ];
        }

        return $structure;
    }

    /**
     * Create default menus for a tenant
     */
    public static function createDefaultMenus(?int $tenantId = null): void
    {
        $plugin = FilamentPanelMenuManagerPlugin::get();
        $structure = static::getDefaultStructure();
        $tenantAttribute = $plugin->getTenantAttribute();

        foreach ($structure as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            if (! empty($children)) {
                // Create group
                $group = Menu::create([
                    $tenantAttribute => $tenantId,
                    'type' => 'group',
                    'reference' => null,
                    'label' => $menuData['label'],
                    'icon' => null,
                    'sort' => $menuData['sort'],
                    'is_visible' => true,
                    'open_in_new_tab' => false,
                ]);

                // Create children
                foreach ($children as $childData) {
                    $reference = $childData['reference'];

                    if (is_array($reference)) {
                        $reference = json_encode($reference);
                    }

                    Menu::create([
                        $tenantAttribute => $tenantId,
                        'parent_id' => $group->id,
                        'type' => $childData['type'] ?? 'route',
                        'reference' => $reference,
                        'label' => $childData['label'],
                        'icon' => $childData['icon'] ?? null,
                        'sort' => $childData['sort'],
                        'is_visible' => true,
                        'open_in_new_tab' => $childData['open_in_new_tab'] ?? false,
                    ]);
                }
            } else {
                // Create standalone item
                $reference = $menuData['reference'];

                if (is_array($reference)) {
                    $reference = json_encode($reference);
                }

                Menu::create([
                    $tenantAttribute => $tenantId,
                    'parent_id' => null,
                    'type' => $menuData['type'] ?? 'route',
                    'reference' => $reference,
                    'label' => $menuData['label'],
                    'icon' => $menuData['icon'] ?? null,
                    'sort' => $menuData['sort'],
                    'is_visible' => true,
                    'open_in_new_tab' => $menuData['open_in_new_tab'] ?? false,
                ]);
            }
        }
    }

    /**
     * Reset menus to default
     */
    public static function resetMenus(?int $tenantId = null): void
    {
        $plugin = FilamentPanelMenuManagerPlugin::get();

        if ($plugin->isMultiTenancyEnabled()) {
            $tenantId = $tenantId ?? auth()->user()->{$plugin->getTenantAttribute()};
            Menu::where($plugin->getTenantAttribute(), $tenantId)->delete();
        } else {
            Menu::query()->delete();
        }

        static::createDefaultMenus($tenantId);
    }
}
