<?php

namespace Ranjeet\FilamentPanelMenuManager\Resources\MenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ranjeet\FilamentPanelMenuManager\Resources\MenuResource;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle standalone items vs groups
        if (! isset($data['type']) || $data['type'] === 'group') {
            $data['type'] = 'group';
            $data['parent_id'] = null;
            $data['reference'] = null;
        } else {
            // Standalone item (route or link)
            $data['parent_id'] = null;

            // For route type: convert reference to JSON
            if ($data['type'] === 'route' && isset($data['reference'])) {
                $routes = collect(filament_routes_with_urls());
                $route = $routes->firstWhere('name', $data['reference']);
                if ($route) {
                    $data['reference'] = json_encode($route);
                }
            }
            // For link type: reference stays as plain string
        }

        return $data;
    }

    protected function afterCreate(): void
    {
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
