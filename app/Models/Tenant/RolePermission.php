<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per (role, action) pair, holding whether that role may do it.
 *
 * A missing row means "not allowed": the table lists grants, so a role only
 * gains an action once somebody explicitly turns it on.
 */
class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = ['role', 'action', 'allowed'];

    protected $casts = ['allowed' => 'boolean'];

    public static function roleHas(string $role, string $action): bool
    {
        return static::where('role', $role)
            ->where('action', $action)
            ->where('allowed', true)
            ->exists();
    }

    /**
     * @return list<string>
     */
    public static function permissionsFor(string $role): array
    {
        return static::where('role', $role)
            ->where('allowed', true)
            ->pluck('action')
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function allGrouped(): array
    {
        return static::where('allowed', true)
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('action')->all())
            ->all();
    }
}
