<?php

namespace App\Mail;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Setting;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends an invoice to the client with its PDF attached.
 *
 * The PDF is rendered when the queued job runs rather than at dispatch, so a
 * correction made in the meantime still reaches the client.
 */
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.invoice_number', ['number' => $this->invoice->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'company' => Setting::get('company_name', config('app.name')),
                'footer' => Setting::get('invoice_footer'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath(InvoiceService::generatePdf($this->invoice))
                ->as('faktura-' . $this->invoice->number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
