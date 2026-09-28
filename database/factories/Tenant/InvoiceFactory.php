<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);

        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'number' => 'FV/' . fake()->unique()->numerify('####/####'),
            'status' => 'draft',
            'currency' => 'PLN',
            'subtotal' => $subtotal,
            'tax_amount' => round($subtotal * 0.23, 2),
            'discount_amount' => 0,
            'total' => round($subtotal * 1.23, 2),
            'paid_amount' => 0,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ];
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent', 'sent_at' => now()]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'paid_amount' => $attributes['total'],
                'paid_at' => now(),
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => 'overdue',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);
    }
}
