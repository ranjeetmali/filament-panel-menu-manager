<?php

namespace Ranjeet\FilamentPanelMenuManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Ranjeet\FilamentPanelMenuManager\FilamentPanelMenuManagerPlugin;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'type',
        'reference',
        'label',
        'icon',
        'sort',
        'is_visible',
        'open_in_new_tab',
        'panel_id',
        'company_id', // Or your tenant attribute
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'sort' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort');
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    public function getReferenceDataAttribute()
    {
        if ($this->type === 'route' && $this->reference) {
            return json_decode($this->reference, true);
        }

        return $this->reference;
    }

    public function setReferenceDataAttribute($value)
    {
        $this->attributes['reference'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeGroups($query)
    {
        return $query->where('type', 'group')->whereNull('parent_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    // ─────────────────────────────────────────────────────────────
    // PERMISSION CHECKING
    // ─────────────────────────────────────────────────────────────

    public function hasPermission($user): bool
    {
        // Groups and links don't have permission checks
        if (in_array($this->type, ['group', 'link'])) {
            return true;
        }

        // Route-based items: check shield_permission from decoded JSON
        if ($this->type === 'route' && $this->reference) {
            $data = json_decode($this->reference, true);
            if (isset($data['shield_permission']) && $data['shield_permission']) {
                return $user->can($data['shield_permission']);
            }
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────
    // CACHE MANAGEMENT
    // ─────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::created(fn () => static::clearMenuCache());
        static::updated(fn () => static::clearMenuCache());
        static::deleted(fn () => static::clearMenuCache());
    }

    public static function clearMenuCache(): void
    {
        $plugin = FilamentPanelMenuManagerPlugin::get();

        if ($plugin->isMultiTenancyEnabled() && auth()->check()) {
            $tenantId = auth()->user()->{$plugin->getTenantAttribute()};
            Cache::forget("menus_{$tenantId}");
        } else {
            Cache::forget('menus_global');
        }
    }

    public static function getMenusForTenant(?int $tenantId = null, $user = null)
    {
        $user = $user ?? auth()->user();
        $cacheKey = $tenantId ? "menus_{$tenantId}" : 'menus_global';

        return Cache::rememberForever($cacheKey, function () use ($tenantId, $user) {
            $query = static::query()
                ->visible()
                ->topLevel()
                ->with(['children' => fn ($q) => $q->visible()->ordered()])
                ->ordered();

            if ($tenantId) {
                $plugin = FilamentPanelMenuManagerPlugin::get();
                $query->where($plugin->getTenantAttribute(), $tenantId);
            }

            return $query->get()->filter(function ($menu) use ($user) {
                if ($menu->type !== 'group' && ! $menu->hasPermission($user)) {
                    return false;
                }

                if ($menu->children) {
                    $menu->setRelation(
                        'children',
                        $menu->children->filter(fn ($child) => $child->hasPermission($user))
                    );
                }

                return true;
            });
        });
    }
}
