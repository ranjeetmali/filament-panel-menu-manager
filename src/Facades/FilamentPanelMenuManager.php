<?php

namespace Ranjeet\FilamentPanelMenuManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ranjeet\FilamentPanelMenuManager\FilamentPanelMenuManager
 */
class FilamentPanelMenuManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Ranjeet\FilamentPanelMenuManager\FilamentPanelMenuManager::class;
    }
}
