<?php

namespace App\Services;

use App\Models\Tenant\Currency;

class CurrencyService
{
    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $fromRate = Currency::where('code', $from)->value('rate_to_base') ?? 1.0;
        $toRate = Currency::where('code', $to)->value('rate_to_base') ?? 1.0;

        return round($amount / $fromRate * $toRate, 2);
    }

    public static function getDefault(): string
    {
        return Currency::where('is_default', true)->value('code') ?? 'PLN';
    }

    public static function format(float $amount, string $currency): string
    {
        $symbol = Currency::where('code', $currency)->value('symbol') ?? $currency;

        return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
    }
}
