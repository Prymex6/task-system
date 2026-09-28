<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientContactFactory extends Factory
{
    protected $model = ClientContact::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'portal_access' => true,
            'is_primary' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
