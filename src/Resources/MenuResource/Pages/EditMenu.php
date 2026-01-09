<?php

namespace Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // For route types, decode the JSON reference and extract just the name for the form
        if (isset($data['type']) && $data['type'] === 'route' && isset($data['reference'])) {
            $referenceData = json_decode($data['reference'], true);
            if (is_array($referenceData) && isset($referenceData['name'])) {
                $data['reference'] = $referenceData['name'];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // For standalone route items (or children in groups), convert reference name to full JSON
        if (isset($data['type']) && $data['type'] === 'route' && isset($data['reference'])) {
            // Check if reference is already JSON (during edit it might be)
            $decoded = json_decode($data['reference'], true);

            // If it's not valid JSON or doesn't have the expected structure, convert it
            if (! is_array($decoded) || ! isset($decoded['name'])) {
                $routes = collect(filament_routes_with_urls());
                $route = $routes->firstWhere('name', $data['reference']);
                if ($route) {
                    $data['reference'] = json_encode($route);
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // For standalone route items, ensure reference is JSON encoded
        if ($this->record->type === 'route' && $this->record->reference) {
            $decoded = json_decode($this->record->reference, true);

            // If it's not valid JSON, convert it
            if (! is_array($decoded) || ! isset($decoded['name'])) {
                $routes = collect(filament_routes_with_urls());
                $route = $routes->firstWhere('name', $this->record->reference);
                if ($route) {
                    $this->record->update(['reference' => json_encode($route)]);
                }
            }
        }

        // Only process children for group types
        if ($this->record->type !== 'group') {
            return;
        }

        // Handle reference field manually since TableRepeater relationship doesn't properly handle
        // conditional fields with same name (both route and link types use 'reference' field name)
        $requestData = request()->input('components.0.snapshot');
        if ($requestData) {
            $snapshot = json_decode($requestData, true);
            $updates = request()->input('components.0.updates', []);

            // Extract reference values from updates
            $referenceUpdates = [];
            foreach ($updates as $key => $value) {
                if (str_contains($key, '.reference')) {
                    // Extract UUID from key like "data.children.UUID.reference"
                    preg_match('/data\.children\.([^.]+)\.reference/', $key, $matches);
                    if (isset($matches[1])) {
                        // Value is a route name, convert to full JSON
                        $routes = collect(filament_routes_with_urls());
                        $route = $routes->firstWhere('name', $value);
                        $referenceUpdates[$matches[1]] = $route ? json_encode($route) : $value;
                    }
                }
            }

            // Match UUIDs to actual children and update their reference field
            if (! empty($referenceUpdates) && isset($snapshot['data']['data'][0]['children'])) {
                $childrenData = $snapshot['data']['data'][0]['children'];
                $savedChildren = $this->record->children()->orderBy('sort')->get();

                $index = 0;
                foreach ($childrenData as $childEntry) {
                    if (is_array($childEntry)) {
                        foreach ($childEntry as $uuid => $data) {
                            if (isset($referenceUpdates[$uuid]) && isset($savedChildren[$index])) {
                                $savedChildren[$index]->update(['reference' => $referenceUpdates[$uuid]]);
                                $index++;
                            }
                        }
                    }
                }
            }
        }
    }
}
