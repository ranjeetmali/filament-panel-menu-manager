<?php

namespace Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.create_menu_or_group')),
        ];
    }
}
