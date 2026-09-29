<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\TaskRecurringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Creates the next copy of every task whose schedule has come round.
 *
 * Runs once per tenant, each in isolation: a failure in one is logged and the
 * run carries on, because one broken workspace must not stop everyone else's
 * tasks from being created.
 */
class GenerateRecurringTasks extends Command
{
    protected $signature = 'tasks:recurring {tenant? : Limit the run to one tenant ID}';

    protected $description = 'Create the next occurrence of every task that is due to repeat';

    public function handle(): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $created = TaskRecurringService::generateDue();

                $this->line(sprintf('  %s: created %d', $tenant->id, $created));
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('  %s: %s', $tenant->id, $e->getMessage()));
                Log::error('tasks:recurring failed', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            } finally {
                tenancy()->end();
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
