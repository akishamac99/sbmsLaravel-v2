<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public static function allows(string $role, string $module, string $action = 'view'): bool
    {
        $columns = [
            'view' => 'can_view',
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
        ];

        $column = $columns[$action] ?? 'can_view';

        return (bool) static::where('role_name', $role)
            ->where('permission_key', $module)
            ->value($column);
    }
}
