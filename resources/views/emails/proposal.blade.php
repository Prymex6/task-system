<x-mail::message>
# {{ $proposal->title }}

Dzień dobry,

w załączeniu przesyłamy naszą propozycję współpracy.

@if ($proposal->total)
Wartość: **{{ number_format((float) $proposal->total, 2, ',', ' ') }} {{ $proposal->currency }}**
@endif

@if ($proposal->valid_until)
Oferta ważna do **{{ $proposal->valid_until->format('d.m.Y') }}**.
@endif

W razie pytań prosimy o kontakt.

Pozdrawiamy,<br>
{{ $company }}
</x-mail::message>
