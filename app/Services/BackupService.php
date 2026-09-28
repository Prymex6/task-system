<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BackupService
{
    public static function createTenantBackup(string $tenantId): ?string
    {
        try {
            $dbName = 'tenant_' . $tenantId;
            $filename = "backup_{$dbName}_" . now()->format('Y-m-d_His') . '.sql';
            $path = storage_path('app/backups/' . $filename);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            $host = config('database.connections.central.host', '127.0.0.1');
            $user = config('database.connections.central.username', 'root');
            $pass = config('database.connections.central.password', '');

            $cmd = "mysqldump --host={$host} --user={$user} --password={$pass} {$dbName} > {$path} 2>&1";
            exec($cmd, $output, $code);

            if ($code !== 0) {
                Log::error("BackupService: mysqldump failed [{$tenantId}]", ['code' => $code]);

                return null;
            }

            Log::info("BackupService: backup created [{$tenantId}]", ['file' => $filename]);

            return $path;
        } catch (\Throwable $e) {
            Log::error("BackupService: exception [{$tenantId}]", ['error' => $e->getMessage()]);

            return null;
        }
    }
}
