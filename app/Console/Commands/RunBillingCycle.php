<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\InvoiceService;
use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Daily billing housekeeping, run once per tenant.
 *
 * Each tenant is handled in isolation: a failure in one is logged and the run
 * continues, because one broken workspace must not stop everyone else from
 * being invoiced.
 */
class RunBillingCycle extends Command
{
    protected $signature = 'billing:cycle {tenant? : Limit the run to one tenant ID}';

    protected $description = 'Issue due recurring invoices and flag overdue ones';

    public function handle(): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('Nie znaleziono tenantów.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $issued = RecurringInvoiceService::processDue();
                InvoiceService::checkOverdue();

                $this->line(sprintf('  %s: wystawiono %d', $tenant->id, $issued));
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('  %s: %s', $tenant->id, $e->getMessage()));
                Log::error('billing:cycle failed', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            } finally {
                tenancy()->end();
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
