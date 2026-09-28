<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wycena {{ $estimate->number }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#1f2937;background:#fff; }
        .page { max-width:800px;margin:0 auto;padding:40px; }
        .header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:20px;border-bottom:2px solid #6366f1; }
        .logo-name { font-size:20px;font-weight:bold;color:#6366f1; }
        .estimate-meta { text-align:right; }
        .estimate-meta .title { font-size:24px;font-weight:bold;color:#111827; }
        .estimate-meta .number { font-size:14px;color:#6b7280;margin-top:4px; }
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
        .footer { border-top:1px solid #e5e7eb;padding-top:16px;font-size:11px;color:#9ca3af;line-height:1.6; }
        .badge { display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:bold;text-transform:uppercase; }
        .badge-draft     { background:#f3f4f6;color:#6b7280; }
        .badge-sent      { background:#dbeafe;color:#1e40af; }
        .badge-accepted  { background:#d1fae5;color:#065f46; }
        .badge-rejected  { background:#fee2e2;color:#991b1b; }
        .badge-expired   { background:#fef3c7;color:#92400e; }
        .validity-box { margin-bottom:20px;padding:14px;background:#eff6ff;border-radius:6px;font-size:12px; }
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
        <div class="estimate-meta">
            <div class="title">WYCENA</div>
            <div class="number">{{ $estimate->number }}</div>
            <div style="margin-top:8px;">
                @php
                    $badgeClass = match($estimate->status) {
                        'sent'     => 'badge-sent',
                        'accepted' => 'badge-accepted',
                        'rejected' => 'badge-rejected',
                        'expired'  => 'badge-expired',
                        default    => 'badge-draft',
                    };
                    $badgeLabel = match($estimate->status) {
                        'sent'     => 'Wysłana',
                        'accepted' => 'Zaakceptowana',
                        'rejected' => 'Odrzucona',
                        'expired'  => 'Wygasła',
                        default    => 'Szkic',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addresses">
        <div class="addr-block">
            <h3>Wystawca</h3>
            <p>
                <strong>{{ $companyName }}</strong><br>
                {{ \App\Models\Tenant\Setting::get('company_address') }}<br>
                @if($nip = \App\Models\Tenant\Setting::get('company_nip'))NIP: {{ $nip }}<br>@endif
                {{ \App\Models\Tenant\Setting::get('company_email') }}<br>
                {{ \App\Models\Tenant\Setting::get('company_phone') }}
            </p>
        </div>
        <div class="addr-block">
            <h3>Klient</h3>
            <p>
                <strong>{{ $estimate->client->company_name ?? $estimate->client->name }}</strong><br>
                @if($estimate->client->nip) NIP: {{ $estimate->client->nip }}<br> @endif
                {{ $estimate->client->address }}<br>
                {{ $estimate->client->postal_code }} {{ $estimate->client->city }}<br>
                {{ $estimate->client->email }}
            </p>
        </div>
    </div>

    <!-- Dates -->
    <div class="dates">
        <div>
            <span>Data wystawienia</span>
            <strong>{{ \Carbon\Carbon::parse($estimate->issue_date)->format('d.m.Y') }}</strong>
        </div>
        <div>
            <span>Ważna do</span>
            <strong>{{ \Carbon\Carbon::parse($estimate->valid_until)->format('d.m.Y') }}</strong>
        </div>
        @if($estimate->project)
        <div>
            <span>Projekt</span>
            <strong>{{ $estimate->project->name }}</strong>
        </div>
        @endif
        <div>
            <span>Waluta</span>
            <strong>{{ $estimate->currency }}</strong>
        </div>
    </div>

    @if($estimate->title)
    <div style="margin-bottom:20px;">
        <h2 style="font-size:16px;color:#111827;">{{ $estimate->title }}</h2>
    </div>
    @endif

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
            @foreach($estimate->items as $i => $item)
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
            <tr><td>Netto:</td><td>{{ number_format($estimate->subtotal, 2, ',', ' ') }} {{ $estimate->currency }}</td></tr>
            <tr><td>VAT:</td><td>{{ number_format($estimate->tax, 2, ',', ' ') }} {{ $estimate->currency }}</td></tr>
            @if($estimate->discount)
            <tr><td>Rabat ({{ $estimate->discount }}%):</td><td>-{{ number_format(($estimate->subtotal + $estimate->tax) * $estimate->discount / 100, 2, ',', ' ') }} {{ $estimate->currency }}</td></tr>
            @endif
            <tr class="total-row">
                <td>RAZEM:</td>
                <td>{{ number_format($estimate->total, 2, ',', ' ') }} {{ $estimate->currency }}</td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    @if($estimate->notes)
    <div style="margin-bottom:20px;padding:14px;background:#f9fafb;border-radius:6px;font-size:12px;color:#6b7280;">
        <strong style="color:#374151;">Uwagi:</strong><br>
        {{ $estimate->notes }}
    </div>
    @endif

    <!-- Validity notice -->
    <div class="validity-box">
        <strong>Ważność oferty:</strong> Niniejsza wycena jest ważna do {{ \Carbon\Carbon::parse($estimate->valid_until)->format('d.m.Y') }}.
        Aby zaakceptować ofertę, prosimy o kontakt lub skorzystanie z portalu klienta.
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ \App\Models\Tenant\Setting::get('invoice_footer', '') }}
        <br>Wygenerowano: {{ now()->format('d.m.Y H:i') }}
    </div>
</div>
</body>
</html>
