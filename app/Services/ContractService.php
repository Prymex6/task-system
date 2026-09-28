<?php

namespace App\Services;

use App\Models\Tenant\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ContractService
{
    public static function generatePdf(Contract $contract): string
    {
        $contract->load(['client', 'type', 'renewals']);
        $pdf = Pdf::loadView('pdf.contract', ['contract' => $contract]);
        $filename = 'contract_' . Str::slug($contract->title ?? $contract->id) . '.pdf';
        $path = storage_path('app/contracts/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf->save($path);

        return $path;
    }

    public static function renew(Contract $contract, array $data): Contract
    {
        $contract->renewals()->create([
            'renewed_at' => now(),
            'old_end_date' => $contract->end_date,
            'new_end_date' => $data['end_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        $contract->update(['end_date' => $data['end_date'], 'status' => 'active']);

        AuditService::log('contract.renewed', $contract);

        return $contract;
    }

    public static function terminate(Contract $contract, string $reason = ''): void
    {
        $contract->update(['status' => 'terminated']);
        AuditService::log('contract.terminated', $contract, [], ['reason' => $reason]);
    }
}
