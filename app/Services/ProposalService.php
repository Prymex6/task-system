<?php

namespace App\Services;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProposalService
{
    public static function generatePdf(Proposal $proposal): string
    {
        $proposal->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.proposal', ['proposal' => $proposal]);
        $filename = 'proposal_' . Str::slug($proposal->title ?? $proposal->id) . '.pdf';
        $path = storage_path('app/proposals/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf->save($path);

        return $path;
    }

    public static function trackView(Proposal $proposal, Request $request): void
    {
        $proposal->views()->create([
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'viewed_at' => now(),
        ]);
    }

    public static function accept(Proposal $proposal): void
    {
        $proposal->update(['status' => 'accepted', 'accepted_at' => now()]);
        AuditService::log('proposal.accepted', $proposal);
    }

    public static function reject(Proposal $proposal): void
    {
        $proposal->update(['status' => 'rejected']);
        AuditService::log('proposal.rejected', $proposal);
    }

    public static function convertToInvoice(Proposal $proposal): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $proposal->client_id,
            'project_id' => $proposal->project_id,
            'number' => InvoiceService::generateNumber(),
            'issue_date' => today(),
            'due_date' => today()->addDays(14),
            'currency' => $proposal->currency ?? 'PLN',
            'subtotal' => $proposal->subtotal,
            'tax' => $proposal->tax,
            'total' => $proposal->total,
            'status' => 'draft',
        ]);

        foreach ($proposal->items as $item) {
            $invoice->items()->create($item->only([
                'description', 'quantity', 'unit_price', 'tax_rate', 'discount', 'subtotal', 'total',
            ]));
        }

        $proposal->update(['converted_to_invoice_id' => $invoice->id]);

        return $invoice;
    }
}
