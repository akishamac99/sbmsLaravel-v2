<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'permID';

    public $timestamps = false;

    protected $fillable = [
        'role_name', 'permission_key', 'can_view', 'can_create', 'can_edit', 'can_delete',
    ];

    // Does this role hold $action (view, create, edit or delete) on $module?
    // If there is no row for the module, the answer is no.
    // A role's permissions are read in one query and kept for 5 minutes (see booted() for when they are cleared),
    // instead of one database query for every menu item and every protected route.
    public static function allows(string $role, string $module, string $action = 'view'): bool
    {
        $columns = [
            'view' => 'can_view',
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
        ];

        $column = $columns[$action] ?? 'can_view';

        return (bool) (static::forRole($role)[$module][$column] ?? false);
    }

    /** @return array<string, array<string, mixed>> every permission row of one role, keyed by module */
    private static function forRole(string $role): array
    {
        return Cache::remember("permissions.{$role}", 300, fn (): array => static::where('role_name', $role)
            ->get()
            ->keyBy('permission_key')
            ->map(fn (self $row): array => $row->only(['can_view', 'can_create', 'can_edit', 'can_delete']))
            ->all());
    }

    protected static function booted(): void
    {
        // Any change to a permission clears that role's saved copy, so the Owner's edits apply at once.
        static::saved(fn (self $row) => Cache::forget("permissions.{$row->role_name}"));
        static::deleted(fn (self $row) => Cache::forget("permissions.{$row->role_name}"));
    }
}
