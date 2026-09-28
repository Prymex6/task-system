<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Umowa {{ $contract->number }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#1f2937;background:#fff; }
        .page { max-width:800px;margin:0 auto;padding:40px; }
        .header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:20px;border-bottom:2px solid #6366f1; }
        .logo-name { font-size:20px;font-weight:bold;color:#6366f1; }
        .contract-meta { text-align:right; }
        .contract-meta .title { font-size:24px;font-weight:bold;color:#111827; }
        .contract-meta .number { font-size:14px;color:#6b7280;margin-top:4px; }
        .parties { display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:28px; }
        .party-block { padding:16px;background:#f9fafb;border-radius:8px; }
        .party-block h3 { font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.08em;margin-bottom:8px; }
        .party-block p { line-height:1.8;font-size:13px; }
        .dates { display:flex;gap:40px;margin-bottom:28px;padding:16px;background:#f9fafb;border-radius:8px; }
        .dates div span { display:block;font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;margin-bottom:2px; }
        .dates div strong { font-size:14px;color:#111827; }
        .section { margin-bottom:24px; }
        .section h2 { font-size:14px;font-weight:bold;color:#374151;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #e5e7eb; }
        .section p { font-size:13px;line-height:1.8;color:#4b5563; }
        .value-box { padding:16px;background:#eff6ff;border-radius:8px;margin-bottom:24px; }
        .value-box strong { font-size:18px;color:#6366f1; }
        .signatures { display:grid;grid-template-columns:1fr 1fr;gap:60px;margin-top:60px; }
        .signature-block { text-align:center; }
        .signature-line { border-top:1px solid #374151;margin-bottom:6px;margin-top:40px; }
        .signature-label { font-size:11px;color:#6b7280; }
        .footer { border-top:1px solid #e5e7eb;padding-top:16px;font-size:11px;color:#9ca3af;line-height:1.6;margin-top:40px; }
        .badge { display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:bold;text-transform:uppercase; }
        .badge-draft    { background:#f3f4f6;color:#6b7280; }
        .badge-active   { background:#d1fae5;color:#065f46; }
        .badge-signed   { background:#dbeafe;color:#1e40af; }
        .badge-expired  { background:#fef3c7;color:#92400e; }
        .badge-cancelled{ background:#fee2e2;color:#991b1b; }
        .signed-notice { padding:12px 16px;background:#d1fae5;border-radius:6px;font-size:12px;color:#065f46;margin-bottom:20px; }
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
        <div class="contract-meta">
            <div class="title">UMOWA</div>
            <div class="number">{{ $contract->number }}</div>
            <div style="margin-top:8px;">
                @php
                    $badgeClass = match($contract->status) {
                        'active'    => 'badge-active',
                        'signed'    => 'badge-signed',
                        'expired'   => 'badge-expired',
                        'cancelled' => 'badge-cancelled',
                        default     => 'badge-draft',
                    };
                    $badgeLabel = match($contract->status) {
                        'active'    => 'Aktywna',
                        'signed'    => 'Podpisana',
                        'expired'   => 'Wygasła',
                        'cancelled' => 'Anulowana',
                        default     => 'Szkic',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
        </div>
    </div>

    @if($contract->signed_at)
    <div class="signed-notice">
        Umowa podpisana elektronicznie przez {{ $contract->client->company_name ?? $contract->client->name }}
        dnia {{ \Carbon\Carbon::parse($contract->signed_at)->format('d.m.Y \o \g\o\d\z\i\n\i\e H:i') }}.
    </div>
    @endif

    <!-- Title -->
    <div style="margin-bottom:24px;text-align:center;">
        <h1 style="font-size:18px;font-weight:bold;color:#111827;">{{ $contract->title }}</h1>
    </div>

    <!-- Parties -->
    <div class="parties">
        <div class="party-block">
            <h3>Zleceniodawca</h3>
            <p>
                <strong>{{ $companyName }}</strong><br>
                {{ \App\Models\Tenant\Setting::get('company_address') }}<br>
                @if($nip = \App\Models\Tenant\Setting::get('company_nip'))NIP: {{ $nip }}<br>@endif
                {{ \App\Models\Tenant\Setting::get('company_email') }}
            </p>
        </div>
        <div class="party-block">
            <h3>Zleceniobiorca / Klient</h3>
            <p>
                <strong>{{ $contract->client->company_name ?? $contract->client->name }}</strong><br>
                @if($contract->client->nip) NIP: {{ $contract->client->nip }}<br> @endif
                {{ $contract->client->address }}<br>
                {{ $contract->client->postal_code }} {{ $contract->client->city }}<br>
                {{ $contract->client->email }}
            </p>
        </div>
    </div>

    <!-- Dates -->
    <div class="dates">
        <div>
            <span>Data zawarcia</span>
            <strong>{{ \Carbon\Carbon::parse($contract->start_date)->format('d.m.Y') }}</strong>
        </div>
        @if($contract->end_date)
        <div>
            <span>Data zakończenia</span>
            <strong>{{ \Carbon\Carbon::parse($contract->end_date)->format('d.m.Y') }}</strong>
        </div>
        @endif
        @if($contract->project)
        <div>
            <span>Projekt</span>
            <strong>{{ $contract->project->name }}</strong>
        </div>
        @endif
    </div>

    <!-- Value -->
    @if($contract->value)
    <div class="value-box">
        <span style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Wartość umowy</span>
        <strong>{{ number_format($contract->value, 2, ',', ' ') }} {{ $contract->currency ?? 'PLN' }}</strong>
    </div>
    @endif

    <!-- Content -->
    @if($contract->content)
    <div class="section">
        <h2>Treść umowy</h2>
        <p>{!! nl2br(e($contract->content)) !!}</p>
    </div>
    @endif

    <!-- Notes -->
    @if($contract->notes)
    <div class="section">
        <h2>Uwagi</h2>
        <p>{{ $contract->notes }}</p>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">{{ $companyName }}<br>Zleceniodawca</div>
        </div>
        <div class="signature-block">
            @if($contract->signed_at)
                <div style="font-size:11px;color:#065f46;margin-bottom:4px;margin-top:20px;">
                    Podpisano elektronicznie<br>{{ \Carbon\Carbon::parse($contract->signed_at)->format('d.m.Y H:i') }}
                </div>
            @endif
            <div class="signature-line"></div>
            <div class="signature-label">{{ $contract->client->company_name ?? $contract->client->name }}<br>Zleceniobiorca</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ \App\Models\Tenant\Setting::get('invoice_footer', '') }}
        <br>Wygenerowano: {{ now()->format('d.m.Y H:i') }}
    </div>
</div>
</body>
</html>
