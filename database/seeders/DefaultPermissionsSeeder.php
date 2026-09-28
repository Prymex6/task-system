<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Zasila domyślne uprawnienia dla ról workspace.
 * Uruchom po utworzeniu nowego tenanta:
 *   php artisan tenants:run db:seed --option="class=DefaultPermissionsSeeder"
 */
class DefaultPermissionsSeeder extends Seeder
{
    /**
     * Mapa: rola => lista dozwolonych akcji.
     * owner i admin mają dostęp do wszystkiego (allowed = true dla wszystkiego).
     * manager, member, guest mają ograniczony dostęp.
     */
    private array $defaults = [
        // ── Projekty ─────────────────────────────────────────────
        'view_projects' => ['admin', 'manager', 'member', 'guest'],
        'create_project' => ['admin', 'manager'],
        'edit_project' => ['admin', 'manager'],
        'delete_project' => ['admin'],
        'archive_project' => ['admin', 'manager'],
        'manage_project_members' => ['admin', 'manager'],

        // ── Zadania ──────────────────────────────────────────────
        'view_tasks' => ['admin', 'manager', 'member', 'guest'],
        'create_task' => ['admin', 'manager', 'member'],
        'edit_task' => ['admin', 'manager', 'member'],
        'delete_task' => ['admin', 'manager'],
        'assign_task' => ['admin', 'manager'],
        'log_time' => ['admin', 'manager', 'member'],
        'manage_sprints' => ['admin', 'manager'],

        // ── CRM ──────────────────────────────────────────────────
        'view_clients' => ['admin', 'manager', 'member'],
        'create_client' => ['admin', 'manager'],
        'edit_client' => ['admin', 'manager'],
        'delete_client' => ['admin'],
        'view_leads' => ['admin', 'manager'],
        'manage_leads' => ['admin', 'manager'],
        'view_deals' => ['admin', 'manager', 'member'],
        'manage_deals' => ['admin', 'manager'],

        // ── Finanse ──────────────────────────────────────────────
        'view_invoices' => ['admin', 'manager'],
        'create_invoice' => ['admin', 'manager'],
        'edit_invoice' => ['admin', 'manager'],
        'delete_invoice' => ['admin'],
        'view_estimates' => ['admin', 'manager'],
        'manage_estimates' => ['admin', 'manager'],
        'view_expenses' => ['admin', 'manager', 'member'],
        'manage_expenses' => ['admin', 'manager', 'member'],
        'view_reports' => ['admin', 'manager'],

        // ── Kontrakty & Propozycje ────────────────────────────────
        'view_contracts' => ['admin', 'manager'],
        'manage_contracts' => ['admin', 'manager'],
        'view_proposals' => ['admin', 'manager'],
        'manage_proposals' => ['admin', 'manager'],

        // ── Support ──────────────────────────────────────────────
        'view_tickets' => ['admin', 'manager', 'member'],
        'manage_tickets' => ['admin', 'manager', 'member'],
        'manage_kb' => ['admin', 'manager'],

        // ── HR ───────────────────────────────────────────────────
        'view_staff' => ['admin', 'manager'],
        'manage_staff' => ['admin'],
        'invite_members' => ['admin', 'manager'],
        'manage_permissions' => ['admin'],
        'view_attendance' => ['admin', 'manager', 'member'],
        'manage_attendance' => ['admin', 'manager'],
        'view_leave' => ['admin', 'manager', 'member'],
        'manage_leave' => ['admin', 'manager'],

        // ── Komunikacja ──────────────────────────────────────────
        'send_messages' => ['admin', 'manager', 'member'],
        'view_announcements' => ['admin', 'manager', 'member', 'guest'],

        // ── Automatyzacje ────────────────────────────────────────
        'manage_automations' => ['admin'],
        'manage_webhooks' => ['admin'],

        // ── Ustawienia ───────────────────────────────────────────
        'manage_settings' => ['admin'],
        'manage_billing' => ['admin'],
        'view_audit_log' => ['admin'],
    ];

    public function run(): void
    {
        $this->command->info('Seedowanie domyślnych uprawnień ról...');

        $roles = ['admin', 'manager', 'member', 'guest'];
        $allActions = array_keys($this->defaults);

        foreach ($roles as $role) {
            foreach ($allActions as $action) {
                $allowed = in_array($role, $this->defaults[$action]);
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'action' => $action],
                    ['allowed' => $allowed, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        $this->command->info('Domyślne uprawnienia gotowe (' . count($allActions) . ' akcji dla ' . count($roles) . ' ról).');
    }
}
