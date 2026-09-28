<?php

namespace App\Console\Commands;

use App\Jobs\CreateBackup;
use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateTenantBackup extends Command
{
    protected $signature = 'backup:tenant {tenant? : Tenant ID} {--type=full : Backup type (full)}';

    protected $description = 'Create a backup for a tenant';

    public function handle(BackupService $backupService): int
    {
        $tenantId = $this->argument('tenant');
        $type = $this->option('type');

        if ($tenantId) {
            $this->info("Dispatching backup job for tenant: {$tenantId}");
            CreateBackup::dispatch($type, $tenantId);
            $this->info('Backup job dispatched.');
        } else {
            // Run synchronously for current context
            $this->info("Creating {$type} backup...");
            $result = $backupService->create($type);
            $this->info("Backup created: {$result['filename']} ({$result['size']} bytes)");
        }

        return self::SUCCESS;
    }
}
