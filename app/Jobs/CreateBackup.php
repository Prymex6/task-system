<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $type = 'full',
        public ?string $tenantId = null,
    ) {}

    public function handle(BackupService $backupService): void
    {
        try {
            if ($this->tenantId) {
                tenancy()->initialize($this->tenantId);
            }

            $result = $backupService->create($this->type);
            Log::info('Backup created', $result);
        } catch (\Exception $e) {
            Log::error('CreateBackup job failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
