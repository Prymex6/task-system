<?php

namespace App\Services;

use App\Models\Tenant\Estimate;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class EstimateService
{
    public static function generateNumber(): string
    {
        $prefix = Setting::get('estimate_prefix', 'WYC');
        $year = now()->year;
        $last = Estimate::whereYear('issue_date', $year)->count() + 1;

        return sprintf('%s/%d/%04d', $prefix, $year, $last);
    }

    public static function convertToInvoice(Estimate $estimate): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $estimate->client_id,
            'project_id' => $estimate->project_id,
            'number' => InvoiceService::generateNumber(),
            'issue_date' => today(),
            'due_date' => today()->addDays(14),
            'currency' => $estimate->currency,
            'subtotal' => $estimate->subtotal,
            'tax' => $estimate->tax,
            'discount' => $estimate->discount,
            'total' => $estimate->total,
            'status' => 'draft',
            'notes' => $estimate->notes,
        ]);

        foreach ($estimate->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'discount' => $item->discount,
                'subtotal' => $item->subtotal,
                'total' => $item->total,
            ]);
        }

        $estimate->update(['converted_to_invoice_id' => $invoice->id, 'status' => 'accepted']);

        return $invoice;
    }

    public static function generatePdf(Estimate $estimate): string
    {
        $estimate->load(['client', 'items']);

        $pdf = Pdf::loadView('pdf.estimate', ['estimate' => $estimate]);
        $filename = 'estimate_' . Str::slug($estimate->number) . '.pdf';
        $path = storage_path('app/estimates/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf->save($path);

        return $path;
    }
}
