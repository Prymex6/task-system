<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Korekta {{ $note->number }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#1f2937;background:#fff; }
        .page { max-width:800px;margin:0 auto;padding:40px; }
        .header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:20px;border-bottom:2px solid #dc2626; }
        .logo-name { font-size:20px;font-weight:bold;color:#dc2626; }
        .meta { text-align:right; }
        .meta .title { font-size:24px;font-weight:bold;color:#111827; }
        .meta .number { font-size:14px;color:#6b7280;margin-top:4px; }
        .addresses { display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:28px; }
        .addr-block h3 { font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.08em;margin-bottom:6px; }
        .addr-block p { line-height:1.7;font-size:13px; }
        .dates { display:flex;gap:40px;margin-bottom:28px;padding:16px;background:#f9fafb;border-radius:8px; }
        .dates div span { display:block;font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;margin-bottom:2px; }
        .dates div strong { font-size:14px;color:#111827; }
        .amount { margin-bottom:28px;padding:20px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;text-align:right; }
        .amount span { display:block;font-size:10px;text-transform:uppercase;color:#991b1b;letter-spacing:.06em;margin-bottom:4px; }
        .amount strong { font-size:24px;color:#dc2626; }
        .reason { margin-bottom:28px; }
        .reason h3 { font-size:10px;text-transform:uppercase;color:#9ca3af;letter-spacing:.08em;margin-bottom:6px; }
        .reason p { line-height:1.7; }
        .footer { border-top:1px solid #e5e7eb;padding-top:16px;font-size:11px;color:#9ca3af;line-height:1.6; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="logo-name">{{ \App\Models\Tenant\Setting::get('company_name') ?: config('app.name') }}</div>
        <div class="meta">
            <div class="title">Faktura korygująca</div>
            <div class="number">{{ $note->number }}</div>
        </div>
    </div>

    <div class="addresses">
        <div class="addr-block">
            <h3>Sprzedawca</h3>
            <p>
                {{ \App\Models\Tenant\Setting::get('company_name') ?: config('app.name') }}<br>
                {{ \App\Models\Tenant\Setting::get('company_address') }}<br>
                @if (\App\Models\Tenant\Setting::get('company_nip'))
                    NIP: {{ \App\Models\Tenant\Setting::get('company_nip') }}
                @endif
            </p>
        </div>
        <div class="addr-block">
            <h3>Nabywca</h3>
            <p>
                {{ $note->invoice?->client?->name ?? '—' }}<br>
                {{ $note->invoice?->client?->address }}<br>
                @if ($note->invoice?->client?->nip)
                    NIP: {{ $note->invoice->client->nip }}
                @endif
            </p>
        </div>
    </div>

    <div class="dates">
        <div>
            <span>Data wystawienia</span>
            <strong>{{ $note->issue_date }}</strong>
        </div>
        <div>
            <span>Korekta do faktury</span>
            <strong>{{ $note->invoice?->number ?? '—' }}</strong>
        </div>
        <div>
            <span>Wartość faktury</span>
            <strong>{{ number_format((float) ($note->invoice?->total ?? 0), 2, ',', ' ') }}</strong>
        </div>
    </div>

    <div class="amount">
        <span>Kwota korekty</span>
        <strong>−{{ number_format((float) $note->amount, 2, ',', ' ') }}</strong>
    </div>

    @if ($note->reason)
        <div class="reason">
            <h3>Przyczyna korekty</h3>
            <p>{{ $note->reason }}</p>
        </div>
    @endif

    <div class="footer">
        Dokument wygenerowany przez system {{ config('app.name') }}.
        Faktura korygująca zmniejsza wartość faktury {{ $note->invoice?->number ?? '' }} o wskazaną kwotę.
    </div>
</div>
</body>
</html>
