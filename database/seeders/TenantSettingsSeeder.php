<?php

namespace Database\Seeders;

use App\Models\Tenant\Setting;
use Illuminate\Database\Seeder;

class TenantSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Company info ──────────────────────────────────────────────────
            'company_name' => 'Mój Workspace',
            'company_email' => '',
            'company_phone' => '',
            'company_address' => '',
            'company_nip' => '',
            'company_bank' => '',

            // ── Appearance ────────────────────────────────────────────────────
            'logo_url' => '',
            'favicon_url' => '',
            'primary_color' => '#6366f1',

            // ── Localization ──────────────────────────────────────────────────
            'timezone' => 'Europe/Warsaw',
            'date_format' => 'd.m.Y',
            'default_currency' => 'PLN',
            'language' => 'pl',

            // ── Invoicing ─────────────────────────────────────────────────────
            'invoice_prefix' => 'FV',
            'estimate_prefix' => 'WY',
            'contract_prefix' => 'UM',
            'invoice_footer' => '',
            'invoice_notes' => 'Dziękujemy za współpracę.',
            'default_tax_rate' => '23',
            'default_payment_terms' => '14',
            'estimate_validity' => '30',

            // ── Notifications ─────────────────────────────────────────────────
            'notify_task_assigned' => '1',
            'notify_task_comment' => '1',
            'notify_invoice_paid' => '1',
            'notify_ticket_new' => '1',
            'notify_ticket_reply' => '1',
            'notify_project_assigned' => '1',
            'notify_mention' => '1',

            // ── HR ────────────────────────────────────────────────────────────
            'work_hours_per_day' => '8',
            'leave_types' => json_encode(['Urlop wypoczynkowy', 'Urlop na żądanie', 'Zwolnienie lekarskie', 'Opieka nad dzieckiem', 'Urlop bezpłatny']),
            'annual_leave_days' => '26',

            // ── Support / Tickets ─────────────────────────────────────────────
            'ticket_departments' => json_encode(['Ogólne', 'Techniczna', 'Finansowa', 'Sprzedaż']),
            'ticket_auto_close_days' => '7',

            // ── Expense categories ────────────────────────────────────────────
            'expense_categories' => json_encode(['Podróże', 'Oprogramowanie', 'Sprzęt', 'Marketing', 'Biuro', 'Usługi', 'Inne']),

            // ── Setup ─────────────────────────────────────────────────────────
            'setup_completed' => true,

            // ── Analytics ─────────────────────────────────────────────────────
            'google_analytics_id' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('  ✔ Ustawienia workspace gotowe (' . count($settings) . ' kluczy).');
    }
}
