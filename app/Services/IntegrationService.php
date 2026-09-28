<?php

namespace App\Services;

use App\Models\Tenant\Integration;

class IntegrationService
{
    public static function get(string $type): ?Integration
    {
        return Integration::where('type', $type)->where('is_active', true)->first();
    }

    public static function isConnected(string $type): bool
    {
        return static::get($type) !== null;
    }

    public static function connect(string $type, array $config): Integration
    {
        return Integration::updateOrCreate(
            ['type' => $type],
            ['config' => $config, 'is_active' => true, 'connected_at' => now()]
        );
    }

    public static function disconnect(string $type): void
    {
        Integration::where('type', $type)->update(['is_active' => false]);
    }
}
