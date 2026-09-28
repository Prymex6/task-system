<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 50, 500);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit' => 'szt.',
            'unit_price' => $unitPrice,
            'tax_rate' => 23,
            'discount_percent' => 0,
            'total' => round($quantity * $unitPrice, 2),
            'order' => 0,
        ];
    }
}
