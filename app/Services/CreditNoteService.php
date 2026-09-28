<?php

namespace App\Services;

use App\Models\Tenant\CreditNote;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class CreditNoteService
{
    public static function generateNumber(): string
    {
        $prefix = Setting::get('credit_note_prefix', 'KOR');
        $year = now()->year;
        $last = CreditNote::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s/%d/%04d', $prefix, $year, $last);
    }

    public static function create(Invoice $invoice, array $data): CreditNote
    {
        $note = CreditNote::create([
            'invoice_id' => $invoice->id,
            'number' => static::generateNumber(),
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'issued_at' => now(),
        ]);

        $remaining = max(0, $invoice->total - $note->amount);
        if ($remaining <= 0) {
            $invoice->update(['status' => 'cancelled']);
        }

        AuditService::log('credit_note.created', $note);

        return $note;
    }

    public static function generatePdf(CreditNote $note): string
    {
        $note->load('invoice.client');
        $pdf = Pdf::loadView('pdf.credit_note', ['note' => $note]);
        $filename = 'credit_note_' . Str::slug($note->number) . '.pdf';
        $path = storage_path('app/credit_notes/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf->save($path);

        return $path;
    }
}
