<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Client;
use App\Models\Tenant\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'subject' => fake()->sentence(),
            'status' => 'open',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }
}
