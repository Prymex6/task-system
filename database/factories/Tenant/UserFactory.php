<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'workspace_role' => 'member',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function manager(): static
    {
        return $this->state(['workspace_role' => 'manager']);
    }

    public function admin(): static
    {
        return $this->state(['workspace_role' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
