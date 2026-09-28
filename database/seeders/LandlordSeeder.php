<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        // ── Plany subskrypcji ──────────────────────────────────────────────
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0.00,
                'max_orders_per_month' => null,
                'features' => json_encode([
                    'max_projects' => 1,
                    'max_users' => 2,
                    'storage_gb' => 1,
                    'time_tracking' => true,
                    'crm' => false,
                    'invoices' => false,
                    'proposals' => false,
                    'contracts' => false,
                    'sprints' => false,
                    'automations' => false,
                    'api_access' => false,
                    'custom_domain' => false,
                    'priority_support' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 49.00,
                'max_orders_per_month' => null,
                'features' => json_encode([
                    'max_projects' => 10,
                    'max_users' => 5,
                    'storage_gb' => 10,
                    'time_tracking' => true,
                    'crm' => true,
                    'invoices' => true,
                    'proposals' => true,
                    'contracts' => false,
                    'sprints' => false,
                    'automations' => false,
                    'api_access' => false,
                    'custom_domain' => false,
                    'priority_support' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 99.00,
                'max_orders_per_month' => null,
                'features' => json_encode([
                    'max_projects' => null,
                    'max_users' => 20,
                    'storage_gb' => 100,
                    'time_tracking' => true,
                    'crm' => true,
                    'invoices' => true,
                    'proposals' => true,
                    'contracts' => true,
                    'sprints' => true,
                    'automations' => true,
                    'api_access' => false,
                    'custom_domain' => false,
                    'priority_support' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price' => 199.00,
                'max_orders_per_month' => null,
                'features' => json_encode([
                    'max_projects' => null,
                    'max_users' => null,
                    'storage_gb' => null,
                    'time_tracking' => true,
                    'crm' => true,
                    'invoices' => true,
                    'proposals' => true,
                    'contracts' => true,
                    'sprints' => true,
                    'automations' => true,
                    'api_access' => true,
                    'custom_domain' => true,
                    'priority_support' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::connection('central')->table('plans')->insertOrIgnore($plans);

        // ── Super Admin ────────────────────────────────────────────────────
        // Credentials come from the environment rather than this file, so
        // nothing usable is committed. The fallback is for local work only and
        // says so out loud when it is used.
        $email = config('app.super_admin.email');
        $password = config('app.super_admin.password');

        DB::connection('central')->table('super_admins')->insertOrIgnore([
            'name' => 'Administrator',
            'email' => $email,
            'password' => Hash::make($password ?: 'password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('  ✔ Plany i super admin gotowe.');
        $this->command->line('  📧 Login: ' . $email);

        if (!$password) {
            $this->command->warn('  ⚠ Użyto hasła domyślnego. Ustaw SUPER_ADMIN_PASSWORD przed wdrożeniem.');
        }
    }
}
