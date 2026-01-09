<?php

namespace Ranjeet\FilamentPanelMenuManager\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Ranjeet\FilamentPanelMenuManager\FilamentPanelMenuManagerPlugin;
use Ranjeet\FilamentPanelMenuManager\Helpers\DefaultMenuHelper;
use Ranjeet\FilamentPanelMenuManager\Helpers\RouteDiscovery;
use Ranjeet\FilamentPanelMenuManager\Models\Menu;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages\CreateMenu;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages\EditMenu;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages\ListMenus;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Menu Builder';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return FilamentPanelMenuManagerPlugin::get()->userCanAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('filament-panel-menu-manager::filament-panel-menu-manager.menu_details'))
                ->description(__('filament-panel-menu-manager::filament-panel-menu-manager.menu_details_description'))
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'group' => __('filament-panel-menu-manager::filament-panel-menu-manager.type_group'),
                            'route' => __('filament-panel-menu-manager::filament-panel-menu-manager.type_route'),
                            'link' => __('filament-panel-menu-manager::filament-panel-menu-manager.type_link'),
                        ])
                        ->required()
                        ->live()
                        ->default('group')
                        ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('filament-panel-menu-manager::filament-panel-menu-manager.type_helper'))
                        ->disabled(fn(?Menu $record) => $record !== null)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('icon')
                        ->helperText(new \Illuminate\Support\HtmlString(
                            'e.g., heroicon-o-home — <a href="https://blade-ui-kit.com/blade-icons#search" target="_blank" rel="noopener">Blade Icons</a>'
                        ))
                        ->maxLength(255)
                        ->visible(fn(Forms\Get $get) => $get('type') !== 'group')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('sort')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->columnSpan(1),

                    Forms\Components\Hidden::make('reference'),

                    // Route selector
                    Forms\Components\Select::make('reference_route')
                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.resource_page'))
                        ->options(fn() => RouteDiscovery::getRouteOptions())
                        ->searchable()
                        ->required(fn(Forms\Get $get) => $get('type') === 'route')
                        ->visible(fn(Forms\Get $get) => $get('type') === 'route')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $routeData = RouteDiscovery::getRouteData($state);
                            $set('reference', json_encode($routeData));
                        })
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Menu $record) {
                            if ($record && $record->type === 'route' && $record->reference) {
                                $data = json_decode($record->reference, true);
                                $component->state($data['name'] ?? null);
                            }
                        })
                        ->dehydrated(false)
                        ->columnSpan(2),

                    // Link input
                    Forms\Components\TextInput::make('reference_link')
                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.url'))
                        ->url()
                        ->required(fn(Forms\Get $get) => $get('type') === 'link')
                        ->visible(fn(Forms\Get $get) => $get('type') === 'link')
                        ->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('reference', $state))
                        ->afterStateHydrated(fn(Forms\Components\TextInput $component, ?Menu $record) =>
                        $record && $record->type === 'link' ? $component->state($record->reference) : null
                        )
                        ->dehydrated(false)
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('is_visible')
                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.visible'))
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('open_in_new_tab')
                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.open_new_tab'))
                        ->visible(fn(Forms\Get $get) => $get('type') === 'link')
                        ->inline(false)
                        ->default(false)
                        ->columnSpan(1),
                ])
                ->columns(4),

            // Child items section (for groups)
            Forms\Components\Section::make(__('filament-panel-menu-manager::filament-panel-menu-manager.child_items'))
                ->description(__('filament-panel-menu-manager::filament-panel-menu-manager.child_items_description'))
                ->visible(fn(Forms\Get $get) => $get('type') === 'group')
                ->schema([
                    Forms\Components\Repeater::make('children')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\Grid::make(6)
                                ->schema([
                                    Forms\Components\TextInput::make('label')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.label'))
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('type')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.type'))
                                        ->options([
                                            'route' => __('filament-panel-menu-manager::filament-panel-menu-manager.type_route'),
                                            'link' => __('filament-panel-menu-manager::filament-panel-menu-manager.type_link'),
                                        ])
                                        ->required()
                                        ->live()
                                        ->default('route')
                                        ->columnSpan(1),

                                    Forms\Components\Hidden::make('reference'),

                                    Forms\Components\Select::make('reference_route')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.resource_page'))
                                        ->options(fn() => RouteDiscovery::getRouteOptions())
                                        ->searchable()
                                        ->required(fn(Forms\Get $get) => $get('type') === 'route')
                                        ->visible(fn(Forms\Get $get) => $get('type') === 'route')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $routeData = RouteDiscovery::getRouteData($state);
                                            $set('reference', json_encode($routeData));
                                        })
                                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Menu $record) {
                                            if ($record && $record->type === 'route' && $record->reference) {
                                                $data = json_decode($record->reference, true);
                                                $component->state($data['name'] ?? null);
                                            }
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('reference_link')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.url'))
                                        ->url()
                                        ->required(fn(Forms\Get $get) => $get('type') === 'link')
                                        ->visible(fn(Forms\Get $get) => $get('type') === 'link')
                                        ->live()
                                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('reference', $state))
                                        ->afterStateHydrated(fn(Forms\Components\TextInput $component, ?Menu $record) =>
                                        $record && $record->type === 'link' ? $component->state($record->reference) : null
                                        )
                                        ->dehydrated(false)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('icon')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.icon'))
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                ]),

                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\Toggle::make('is_visible')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.visible'))
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpan(1),

                                    Forms\Components\Toggle::make('open_in_new_tab')
                                        ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.open_new_tab'))
                                        ->visible(fn(Forms\Get $get) => $get('type') === 'link')
                                        ->default(false)
                                        ->inline(false)
                                        ->columnSpan(1),
                                ]),
                        ])
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->orderColumn('sort')
                        ->cloneable()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                        ->addActionLabel(__('filament-panel-menu-manager::filament-panel-menu-manager.add_menu_item'))
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->topLevel()->ordered())
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'group' => 'Group',
                        'route' => 'Route',
                        'link' => 'Link',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'group' => 'gray',
                        'route' => 'success',
                        'link' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('icon')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Items')
                    ->alignCenter()
                    ->badge(),
            ])
            ->defaultSort('sort')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->placeholder('All')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('reset')
                    ->label(__('filament-panel-menu-manager::filament-panel-menu-manager.reset_to_default'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        DefaultMenuHelper::resetMenus();
                        Notification::make()
                            ->success()
                            ->title(__('filament-panel-menu-manager::filament-panel-menu-manager.reset_success'))
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
