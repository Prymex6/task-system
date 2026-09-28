<?php

namespace App\Services;

use App\Models\Tenant\TaxRate;

class TaxService
{
    public static function calculate(float $net, float $taxPercent): array
    {
        $tax = round($net * $taxPercent / 100, 2);
        $gross = round($net + $tax, 2);

        return ['net' => $net, 'tax' => $tax, 'gross' => $gross];
    }

    public static function getActive(): array
    {
        return TaxRate::where('is_active', true)->orderBy('rate')->get()->toArray();
    }

    public static function getDefault(): ?TaxRate
    {
        return TaxRate::where('is_default', true)->first();
    }

    public static function fromGross(float $gross, float $taxPercent): array
    {
        $net = round($gross / (1 + $taxPercent / 100), 2);
        $tax = round($gross - $net, 2);

        return ['net' => $net, 'tax' => $tax, 'gross' => $gross];
    }
}
