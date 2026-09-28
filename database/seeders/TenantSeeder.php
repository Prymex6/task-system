<?php

namespace Database\Seeders;

use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data, for development and testing only.
 * Creates sample staff, clients, projects and tasks.
 *
 * Use: php artisan tenants:run db:seed --option="class=TenantSeeder"
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('⛔ TenantSeeder zawiera dane demonstracyjne i NIE powinien być uruchamiany w środowisku produkcyjnym!');
            if (!$this->command->confirm('Czy na pewno chcesz zasiać dane demo w produkcji?', false)) {
                $this->command->info('Anulowano.');

                return;
            }
        }

        $this->call(DefaultPermissionsSeeder::class);
        $this->call(TenantSettingsSeeder::class);
        $this->call(EmailTemplateSeeder::class);

        $this->seedStaff();
        $this->seedClients();
        $this->seedProjects();

        $this->command->info('  ✔ Dane demonstracyjne tenanta gotowe.');
    }

    // ── Staff ──────────────────────────────────────────────────────────────────

    private function seedStaff(): void
    {
        $staff = [
            [
                'name' => 'Jan Kowalski',
                'email' => 'owner@example.com',
                'password' => Hash::make('password'),
                'workspace_role' => 'owner',
                'timezone' => 'Europe/Warsaw',
                'language' => 'pl',
                'is_active' => true,
            ],
            [
                'name' => 'Anna Nowak',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'workspace_role' => 'admin',
                'timezone' => 'Europe/Warsaw',
                'language' => 'pl',
                'is_active' => true,
            ],
            [
                'name' => 'Piotr Wiśniewski',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'workspace_role' => 'manager',
                'timezone' => 'Europe/Warsaw',
                'language' => 'pl',
                'is_active' => true,
            ],
            [
                'name' => 'Katarzyna Zielińska',
                'email' => 'member@example.com',
                'password' => Hash::make('password'),
                'workspace_role' => 'member',
                'timezone' => 'Europe/Warsaw',
                'language' => 'pl',
                'is_active' => true,
            ],
        ];

        foreach ($staff as $member) {
            User::updateOrCreate(['email' => $member['email']], $member);
        }

        $this->command->line('  👥 Staff: ' . count($staff) . ' kont.');
    }

    // ── Klienci ────────────────────────────────────────────────────────────────

    private function seedClients(): void
    {
        $clients = [
            [
                'name' => 'Acme Corp',
                'company_name' => 'Acme Corporation Sp. z o.o.',
                'email' => 'kontakt@acme.pl',
                'phone' => '+48 22 123 45 67',
                'website' => 'https://acme.pl',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country' => 'PL',
                'currency' => 'PLN',
                'is_active' => true,
            ],
            [
                'name' => 'TechStart',
                'company_name' => 'TechStart S.A.',
                'email' => 'hello@techstart.io',
                'phone' => '+48 12 456 78 90',
                'website' => 'https://techstart.io',
                'city' => 'Kraków',
                'postal_code' => '30-001',
                'country' => 'PL',
                'currency' => 'PLN',
                'is_active' => true,
            ],
        ];

        foreach ($clients as $clientData) {
            $client = Client::updateOrCreate(['email' => $clientData['email']], $clientData);

            // Dodaj kontakt do portalu
            $portalEmail = 'portal@' . strtolower(explode('@', $clientData['email'])[1]);
            ClientContact::updateOrCreate(
                ['email' => $portalEmail],
                [
                    'client_id' => $client->id,
                    'name' => 'Kontakt ' . $clientData['name'],
                    'email' => $portalEmail,
                    'password' => Hash::make('password'),
                    'is_primary' => true,
                    'portal_access' => true,
                ]
            );
        }

        $this->command->line('  🏢 Klienci: ' . count($clients) . ' firm.');
    }

    // ── Projekty i zadania ─────────────────────────────────────────────────────

    private function seedProjects(): void
    {
        $owner = User::where('email', 'owner@example.com')->first();
        $manager = User::where('email', 'manager@example.com')->first();
        $member = User::where('email', 'member@example.com')->first();
        $client = Client::where('email', 'kontakt@acme.pl')->first();

        if (!$owner || !$client) {
            $this->command->warn('  ⚠ Brak wymaganych danych do stworzenia projektów.');

            return;
        }

        // Task statuses
        $statuses = [
            ['name' => 'Do zrobienia',  'color' => '#94a3b8', 'order' => 1, 'is_default' => true,  'is_closed' => false],
            ['name' => 'W trakcie',     'color' => '#3b82f6', 'order' => 2, 'is_default' => false, 'is_closed' => false],
            ['name' => 'W recenzji',    'color' => '#f59e0b', 'order' => 3, 'is_default' => false, 'is_closed' => false],
            ['name' => 'Gotowe',        'color' => '#22c55e', 'order' => 4, 'is_default' => false, 'is_closed' => true],
        ];

        foreach ($statuses as $s) {
            TaskStatus::updateOrCreate(['name' => $s['name']], $s);
        }

        $todoStatus = TaskStatus::where('name', 'Do zrobienia')->first();
        $inProgress = TaskStatus::where('name', 'W trakcie')->first();

        // Projekt 1
        $project1 = Project::updateOrCreate(
            ['name' => 'Redesign strony głównej Acme'],
            [
                'client_id' => $client->id,
                'description' => 'Kompleksowy redesign strony głównej z nowym UI/UX.',
                'status' => 'in_progress',
                'visibility' => 'team',
                'start_date' => now()->subDays(14)->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'created_by' => $owner->id,
            ]
        );

        // Put the sample staff on the project
        $project1->members()->syncWithoutDetaching([
            $owner->id => ['project_role' => 'project_manager'],
            $manager->id => ['project_role' => 'contributor'],
            $member->id => ['project_role' => 'contributor'],
        ]);

        // Zadania projektu 1
        $tasks1 = [
            ['title' => 'Analiza wymagań UI/UX',    'priority' => 'high',   'task_status_id' => $inProgress?->id, 'assigned_to' => $manager->id],
            ['title' => 'Wireframes desktop',        'priority' => 'high',   'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
            ['title' => 'Wireframes mobile',         'priority' => 'medium', 'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
            ['title' => 'Implementacja homepage',    'priority' => 'high',   'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
            ['title' => 'Testy cross-browser',       'priority' => 'low',    'task_status_id' => $todoStatus?->id, 'assigned_to' => $manager->id],
        ];

        foreach ($tasks1 as $taskData) {
            $assignedTo = $taskData['assigned_to'];
            unset($taskData['assigned_to']);
            $task = Task::updateOrCreate(
                ['project_id' => $project1->id, 'title' => $taskData['title']],
                array_merge($taskData, ['project_id' => $project1->id, 'created_by' => $owner->id])
            );
            $task->assignees()->syncWithoutDetaching([$assignedTo]);
        }

        // Projekt 2
        $project2 = Project::updateOrCreate(
            ['name' => 'Aplikacja mobilna TechStart'],
            [
                'description' => 'Aplikacja mobilna React Native dla klienta TechStart.',
                'status' => 'planning',
                'visibility' => 'team',
                'start_date' => now()->addDays(7)->toDateString(),
                'due_date' => now()->addDays(90)->toDateString(),
                'created_by' => $owner->id,
            ]
        );

        $project2->members()->syncWithoutDetaching([
            $owner->id => ['project_role' => 'project_manager'],
            $member->id => ['project_role' => 'contributor'],
        ]);

        $tasks2 = [
            ['title' => 'Setup środowiska RN',      'priority' => 'high',   'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
            ['title' => 'Architektura nawigacji',   'priority' => 'high',   'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
            ['title' => 'Integracja z API',         'priority' => 'medium', 'task_status_id' => $todoStatus?->id, 'assigned_to' => $member->id],
        ];

        foreach ($tasks2 as $taskData) {
            $assignedTo = $taskData['assigned_to'];
            unset($taskData['assigned_to']);
            $task = Task::updateOrCreate(
                ['project_id' => $project2->id, 'title' => $taskData['title']],
                array_merge($taskData, ['project_id' => $project2->id, 'created_by' => $owner->id])
            );
            $task->assignees()->syncWithoutDetaching([$assignedTo]);
        }

        $this->command->line('  📁 Projekty: 2 szt., Zadania: ' . (count($tasks1) + count($tasks2)) . ' szt.');
    }
}
