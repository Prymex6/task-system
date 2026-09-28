<x-mail::message>
# Faktura {{ $invoice->number }}

Dzień dobry,

w załączeniu przesyłamy fakturę **{{ $invoice->number }}** na kwotę
**{{ number_format((float) $invoice->total, 2, ',', ' ') }} {{ $invoice->currency }}**.

@if ($invoice->due_date)
Termin płatności: **{{ $invoice->due_date->format('d.m.Y') }}**
@endif

@if ($footer)
{{ $footer }}
@endif

Pozdrawiamy,<br>
{{ $company }}
</x-mail::message>
