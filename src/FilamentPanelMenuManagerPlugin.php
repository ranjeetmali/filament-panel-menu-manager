<?php

namespace Ranjeet\FilamentPanelMenuManager;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Ranjeet\FilamentPanelMenuManager\Models\Menu;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource;

class FilamentPanelMenuManagerPlugin implements Plugin
{

    protected bool $enableMultiTenancy = false;
    protected ?string $tenantAttribute = 'company_id';
    protected ?string $tenantRelationship = null;
    protected bool $autoRegisterNavigation = true;
    protected bool $registerInUserMenu = true;
    protected ?array $defaultMenuStructure = null;
    protected ?\Closure $canAccessCallback = null;

    public function getId(): string
    {
        return 'filament-panel-menu-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MenuResource::class,
        ]);

        if ($this->autoRegisterNavigation) {
            $panel->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $this->buildNavigation($builder);
            });
        }
    }

    public function boot(Panel $panel): void
    {
        if ($this->registerInUserMenu) {
            $this->registerUserMenuItem();
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    // Configuration Methods (Fluent API)

    public function enableMultiTenancy(bool $enable = true): static
    {
        $this->enableMultiTenancy = $enable;
        return $this;
    }

    public function tenantAttribute(string $attribute): static
    {
        $this->tenantAttribute = $attribute;
        return $this;
    }

    public function tenantRelationship(string $relationship): static
    {
        $this->tenantRelationship = $relationship;
        return $this;
    }

    public function autoRegisterNavigation(bool $register = true): static
    {
        $this->autoRegisterNavigation = $register;
        return $this;
    }

    public function registerInUserMenu(bool $register = true): static
    {
        $this->registerInUserMenu = $register;
        return $this;
    }

    public function defaultMenuStructure(array $structure): static
    {
        $this->defaultMenuStructure = $structure;
        return $this;
    }

    public function canAccess(\Closure $callback): static
    {
        $this->canAccessCallback = $callback;
        return $this;
    }

    // Getters

    public function isMultiTenancyEnabled(): bool
    {
        return $this->enableMultiTenancy;
    }

    public function getTenantAttribute(): string
    {
        return $this->tenantAttribute;
    }

    public function shouldAutoRegisterNavigation(): bool
    {
        return $this->autoRegisterNavigation;
    }

    public function shouldRegisterInUserMenu(): bool
    {
        return $this->registerInUserMenu;
    }

    public function getDefaultMenuStructure(): ?array
    {
        return $this->defaultMenuStructure;
    }

    public function userCanAccess(): bool
    {
        if ($this->canAccessCallback) {
            return call_user_func($this->canAccessCallback, auth()->user());
        }

        return auth()->user()?->is_admin ?? false;
    }

    // Navigation Builder

    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        if (!auth()->check()) {
            return $builder;
        }

        $menus = $this->getMenusForCurrentTenant();

        foreach ($menus as $menu) {
            if ($menu->type === 'group') {
                // Build group with its children
                $items = [];
                foreach ($menu->children as $child) {
                    $navItem = $this->createNavigationItem($child);
                    if ($navItem) {
                        $items[] = $navItem;
                    }
                }

                if (!empty($items)) {
                    $builder->group(
                        NavigationGroup::make($menu->label)
                            ->items($items)
                    );
                }
            } else {
                // Standalone item (not in a group)
                $navItem = $this->createNavigationItem($menu);
                if ($navItem) {
                    $builder->items([$navItem]);
                }
            }
        }

        return $builder;
    }

    protected function registerUserMenuItem(): void
    {
        Filament::serving(function () {
            if (!auth()->check() || !$this->userCanAccess()) {
                return;
            }

            Filament::registerUserMenuItems([
                'menu-builder' => \Filament\Navigation\MenuItem::make()
                    ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.menu_builder'))
                    ->icon('heroicon-o-bars-3')
                    ->url(MenuResource::getUrl('index'))
                    ->sort(0),
            ]);
        });
    }

    protected function getMenusForCurrentTenant()
    {
        $query = Menu::query()->visible()->topLevel()->ordered()
            ->with(['children' => fn($q) => $q->visible()->ordered()]);

        if ($this->enableMultiTenancy && $this->tenantAttribute) {
            $tenantId = auth()->user()->{$this->tenantAttribute};
            $query->where($this->tenantAttribute, $tenantId);
        }

        return $query->get()->filter(function ($menu) {
            if ($menu->type !== 'group' && !$menu->hasPermission(auth()->user())) {
                return false;
            }

            if ($menu->children) {
                $menu->setRelation(
                    'children',
                    $menu->children->filter(fn($child) => $child->hasPermission(auth()->user()))
                );
            }

            return true;
        });
    }

    protected function createNavigationItem($item, $groupLabel = null): ?NavigationItem
    {
        if (!$item->hasPermission(auth()->user())) {
            return null;
        }

        $navItem = NavigationItem::make($item->label);

        if ($item->type === 'link') {
            $navItem->url($item->reference)
                ->openUrlInNewTab($item->open_in_new_tab)
                ->isActiveWhen(fn(): bool => request()->url() === $item->reference);
        } elseif ($item->type === 'route' && $item->reference) {
            $data = json_decode($item->reference, true);
            $url = $data['url'] ?? '#';
            $navItem->url($url)
                ->isActiveWhen(fn() => request()->url() === $url);
        }

        if ($item->icon) {
            $navItem->icon($item->icon);
        }

        if ($groupLabel) {
            $navItem->group($groupLabel);
        }

        $navItem->sort($item->sort);

        return $navItem;
    }
}
