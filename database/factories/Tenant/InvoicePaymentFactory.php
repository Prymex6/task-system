<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoicePaymentFactory extends Factory
{
    protected $model = InvoicePayment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_method_id' => null,
            'payment_method' => 'bank_transfer',
            'amount' => fake()->randomFloat(2, 50, 1000),
            'currency' => 'PLN',
            'payment_date' => now()->toDateString(),
            'reference' => fake()->bothify('REF-####'),
            'gateway_id' => null,
            'gateway' => 'manual',
            'notes' => null,
        ];
    }
}
