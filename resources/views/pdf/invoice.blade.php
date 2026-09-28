<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Faktura {{ $invoice->number }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#1f2937;background:#fff; }
        .page { max-width:800px;margin:0 auto;padding:40px; }
        .header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:20px;border-bottom:2px solid #6366f1; }
        .logo-name { font-size:20px;font-weight:bold;color:#6366f1; }
        .invoice-meta { text-align:right; }
        .invoice-meta .title { font-size:24px;font-weight:bold;color:#111827; }
        .invoice-meta .number { font-size:14px;color:#6b7280;margin-top:4px; }
        .addresses { display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:28px; }
        .addr-block h3 { font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.08em;margin-bottom:6px; }
        .addr-block p { line-height:1.7;font-size:13px; }
        .dates { display:flex;gap:40px;margin-bottom:28px;padding:16px;background:#f9fafb;border-radius:8px; }
        .dates div span { display:block;font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;margin-bottom:2px; }
        .dates div strong { font-size:14px;color:#111827; }
        table { width:100%;border-collapse:collapse;margin-bottom:20px; }
        thead th { background:#f3f4f6;padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb; }
        thead th:not(:first-child) { text-align:right; }
        tbody td { padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:13px; }
        tbody td:not(:first-child) { text-align:right; }
        .totals { display:flex;flex-direction:column;align-items:flex-end;margin-bottom:28px; }
        .totals table { width:260px; }
        .totals td { padding:5px 10px;font-size:13px; }
        .totals .total-row td { font-size:16px;font-weight:bold;color:#6366f1;border-top:2px solid #6366f1;padding-top:10px; }
        .balance-row td { color:#ef4444;font-weight:bold; }
        .footer { border-top:1px solid #e5e7eb;padding-top:16px;font-size:11px;color:#9ca3af;line-height:1.6; }
        .badge { display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:bold;text-transform:uppercase; }
        .badge-paid { background:#d1fae5;color:#065f46; }
        .badge-sent { background:#dbeafe;color:#1e40af; }
        .badge-overdue { background:#fee2e2;color:#991b1b; }
        .badge-draft { background:#f3f4f6;color:#6b7280; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div>
            @php $companyName = \App\Models\Tenant\Setting::get('company_name', config('app.name')); @endphp
            <div class="logo-name">{{ $companyName }}</div>
            @if($addr = \App\Models\Tenant\Setting::get('company_address'))
                <p style="color:#6b7280;margin-top:4px;font-size:12px;">{{ $addr }}</p>
            @endif
            @if($nip = \App\Models\Tenant\Setting::get('company_nip'))
                <p style="color:#6b7280;font-size:12px;">NIP: {{ $nip }}</p>
            @endif
        </div>
        <div class="invoice-meta">
            <div class="title">FAKTURA VAT</div>
            <div class="number">{{ $invoice->number }}</div>
            <div style="margin-top:8px;">
                @php
                    $badgeClass = match($invoice->status) {
                        'paid' => 'badge-paid', 'sent' => 'badge-sent', 'overdue' => 'badge-overdue', default => 'badge-draft'
                    };
                    $badgeLabel = match($invoice->status) {
                        'paid' => 'Opłacona', 'sent' => 'Wysłana', 'overdue' => 'Przeterminowana',
                        'partially_paid' => 'Częściowo opłacona', 'cancelled' => 'Anulowana', default => 'Szkic'
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addresses">
        <div class="addr-block">
            <h3>Sprzedawca</h3>
            <p>
                <strong>{{ $companyName }}</strong><br>
                {{ \App\Models\Tenant\Setting::get('company_address') }}<br>
                @if($nip = \App\Models\Tenant\Setting::get('company_nip'))NIP: {{ $nip }}<br>@endif
                {{ \App\Models\Tenant\Setting::get('company_email') }}<br>
                {{ \App\Models\Tenant\Setting::get('company_phone') }}
            </p>
        </div>
        <div class="addr-block">
            <h3>Nabywca</h3>
            <p>
                <strong>{{ $invoice->client->company_name ?? $invoice->client->name }}</strong><br>
                @if($invoice->client->nip) NIP: {{ $invoice->client->nip }}<br> @endif
                {{ $invoice->client->address }}<br>
                {{ $invoice->client->postal_code }} {{ $invoice->client->city }}<br>
                {{ $invoice->client->email }}
            </p>
        </div>
    </div>

    <!-- Dates -->
    <div class="dates">
        <div>
            <span>Data wystawienia</span>
            <strong>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</strong>
        </div>
        <div>
            <span>Termin płatności</span>
            <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</strong>
        </div>
        @if($invoice->project)
        <div>
            <span>Projekt</span>
            <strong>{{ $invoice->project->name }}</strong>
        </div>
        @endif
        <div>
            <span>Waluta</span>
            <strong>{{ $invoice->currency }}</strong>
        </div>
    </div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Opis</th>
                <th>Ilość</th>
                <th>Cena netto</th>
                <th>VAT %</th>
                <th>VAT</th>
                <th>Wartość brutto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                <td>{{ $item->tax_rate ?? 0 }}%</td>
                <td>{{ number_format($item->tax, 2, ',', ' ') }}</td>
                <td>{{ number_format($item->total, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr><td>Netto:</td><td>{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
            <tr><td>VAT:</td><td>{{ number_format($invoice->tax, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
            @if($invoice->discount)
            <tr><td>Rabat ({{ $invoice->discount }}%):</td><td>-{{ number_format(($invoice->subtotal + $invoice->tax) * $invoice->discount / 100, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
            @endif
            <tr class="total-row">
                <td>RAZEM:</td>
                <td>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            @if($invoice->balance_due > 0)
            <tr class="balance-row">
                <td>Do zapłaty:</td>
                <td>{{ number_format($invoice->balance_due, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Notes -->
    @if($invoice->notes)
    <div style="margin-bottom:20px;padding:14px;background:#f9fafb;border-radius:6px;font-size:12px;color:#6b7280;">
        <strong style="color:#374151;">Uwagi:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif

    @if($bankAccount = \App\Models\Tenant\Setting::get('company_bank_account'))
    <div style="margin-bottom:20px;padding:14px;background:#eff6ff;border-radius:6px;font-size:12px;">
        <strong>Numer konta bankowego:</strong> {{ $bankAccount }}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        {{ \App\Models\Tenant\Setting::get('invoice_footer', '') }}
        <br>Wygenerowano: {{ now()->format('d.m.Y H:i') }}
    </div>
</div>
</body>
</html>
