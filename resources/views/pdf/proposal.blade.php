<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8"/>
<style>
    @font-face {
        font-family: 'DejaVu Sans';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path("fonts/DejaVuSans.ttf") }}') format('truetype');
    }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
    .page { padding: 40px 50px; }
    .header { display: table; width: 100%; margin-bottom: 30px; }
    .company-block { display: table-cell; width: 55%; vertical-align: top; }
    .meta-block { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
    .company-name { font-size: 18px; font-weight: bold; color: #6366f1; margin-bottom: 4px; }
    .company-info { color: #6b7280; font-size: 10px; line-height: 1.6; }
    .doc-title { font-size: 22px; font-weight: bold; color: #111827; margin-bottom: 6px; }
    .doc-number { font-size: 13px; color: #6366f1; font-weight: bold; }
    .divider { border: none; border-top: 2px solid #6366f1; margin: 20px 0; }
    .parties { display: table; width: 100%; margin-bottom: 25px; }
    .party-block { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
    .party-label { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 5px; }
    .party-name { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 3px; }
    .party-detail { font-size: 10px; color: #6b7280; line-height: 1.5; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1; margin-bottom: 10px; margin-top: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
    .content-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; font-size: 11px; line-height: 1.7; color: #374151; white-space: pre-wrap; }
    .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .details-table td { padding: 6px 10px; font-size: 10px; border-bottom: 1px solid #f3f4f6; }
    .details-table td:first-child { color: #9ca3af; width: 35%; }
    .details-table td:last-child { font-weight: bold; color: #111827; }
    .value-box { background: #eef2ff; border: 2px solid #6366f1; border-radius: 8px; padding: 12px 20px; display: inline-block; text-align: center; margin-top: 15px; }
    .value-label { font-size: 9px; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; }
    .value-amount { font-size: 24px; font-weight: bold; color: #4f46e5; margin-top: 3px; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-draft { background: #f3f4f6; color: #6b7280; }
    .status-sent { background: #dbeafe; color: #1d4ed8; }
    .status-accepted { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-expired { background: #fef3c7; color: #92400e; }
    .footer { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 15px; text-align: center; color: #9ca3af; font-size: 9px; }
    .validity-notice { background: #fef9c3; border: 1px solid #fde047; border-radius: 6px; padding: 10px 15px; margin-top: 20px; font-size: 10px; color: #713f12; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="company-block">
            <div class="company-name">{{ $setting['company_name'] ?? config('app.name') }}</div>
            <div class="company-info">
                @if(!empty($setting['company_address'])){{ $setting['company_address'] }}<br>@endif
                @if(!empty($setting['company_email'])){{ $setting['company_email'] }}<br>@endif
                @if(!empty($setting['company_phone'])){{ $setting['company_phone'] }}<br>@endif
                @if(!empty($setting['company_nip']))NIP: {{ $setting['company_nip'] }}@endif
            </div>
        </div>
        <div class="meta-block">
            <div class="doc-title">PROPOZYCJA HANDLOWA</div>
            <div class="doc-number">{{ $proposal->number }}</div>
            <div style="margin-top: 8px;">
                @php
                    $statusLabels = ['draft' => 'Szkic', 'sent' => 'Wysłana', 'accepted' => 'Zaakceptowana', 'rejected' => 'Odrzucona', 'expired' => 'Wygasła'];
                    $statusClass = 'status-' . $proposal->status;
                @endphp
                <span class="status-badge {{ $statusClass }}">{{ $statusLabels[$proposal->status] ?? $proposal->status }}</span>
            </div>
            <div style="margin-top: 10px; font-size: 10px; color: #6b7280;">
                Data: {{ $proposal->created_at->format('d.m.Y') }}
            </div>
        </div>
    </div>

    <hr class="divider"/>

    <div class="parties">
        <div class="party-block">
            <div class="party-label">Przygotował</div>
            <div class="party-name">{{ $setting['company_name'] ?? config('app.name') }}</div>
            @if(!empty($setting['company_address']))
            <div class="party-detail">{{ $setting['company_address'] }}</div>
            @endif
            @if(!empty($setting['company_nip']))
            <div class="party-detail">NIP: {{ $setting['company_nip'] }}</div>
            @endif
        </div>
        <div class="party-block">
            <div class="party-label">Dla klienta</div>
            <div class="party-name">{{ $proposal->client->company_name ?: $proposal->client->name }}</div>
            @if($proposal->client->nip)
            <div class="party-detail">NIP: {{ $proposal->client->nip }}</div>
            @endif
            @if($proposal->client->address)
            <div class="party-detail">{{ $proposal->client->address }}</div>
            @endif
        </div>
    </div>

    <div class="section-title">Szczegóły propozycji</div>
    <table class="details-table">
        <tr>
            <td>Tytuł</td>
            <td>{{ $proposal->title }}</td>
        </tr>
        @if($proposal->project)
        <tr>
            <td>Projekt</td>
            <td>{{ $proposal->project->name }}</td>
        </tr>
        @endif
        @if($proposal->valid_until)
        <tr>
            <td>Ważna do</td>
            <td>{{ \Carbon\Carbon::parse($proposal->valid_until)->format('d.m.Y') }}</td>
        </tr>
        @endif
        @if($proposal->sent_at)
        <tr>
            <td>Data wysłania</td>
            <td>{{ $proposal->sent_at->format('d.m.Y') }}</td>
        </tr>
        @endif
    </table>

    @if($proposal->content)
    <div class="section-title">Treść propozycji</div>
    <div class="content-box">{{ $proposal->content }}</div>
    @endif

    @if($proposal->value)
    <div style="text-align: right; margin-top: 20px;">
        <div class="value-box">
            <div class="value-label">Łączna wartość</div>
            <div class="value-amount">{{ number_format($proposal->value, 2, ',', ' ') }} {{ $proposal->currency ?? 'PLN' }}</div>
        </div>
    </div>
    @endif

    @if($proposal->valid_until && \Carbon\Carbon::parse($proposal->valid_until)->isFuture())
    <div class="validity-notice">
        ⏳ Niniejsza propozycja jest ważna do dnia <strong>{{ \Carbon\Carbon::parse($proposal->valid_until)->format('d.m.Y') }}</strong>.
        Po tym terminie wartości mogą ulec zmianie.
    </div>
    @endif

    <div class="footer">
        Dokument wygenerowany {{ now()->format('d.m.Y H:i') }} · {{ $setting['company_name'] ?? config('app.name') }}
    </div>
</div>
</body>
</html>
