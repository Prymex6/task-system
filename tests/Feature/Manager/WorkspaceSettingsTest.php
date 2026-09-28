<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\CustomField;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Integration;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use Tests\TenantTestCase;

/**
 * The settings screens that answered "Feature coming soon".
 *
 * Company and finance details, notification switches, custom fields,
 * integrations and e-mail templates all had a page built but no controller
 * behind it.
 */
class WorkspaceSettingsTest extends TenantTestCase
{
    public function test_company_details_are_saved(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.company.update'), [
                'company_name' => 'Kowalski sp. z o.o.',
                'company_nip' => '1234563218',
                'company_city' => 'Kraków',
            ])
            ->assertRedirect();

        $this->assertSame('Kowalski sp. z o.o.', Setting::get('company_name'));
        $this->assertSame('Kraków', Setting::get('company_city'));
    }

    public function test_the_company_page_does_not_demand_unrelated_settings(): void
    {
        // It used to post to the general endpoint, which required workspace_name
        // and timezone, so saving company details always came back 422.
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.company.update'), ['company_name' => 'Tylko nazwa'])
            ->assertSessionHasNoErrors();
    }

    public function test_finance_settings_require_a_currency(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.finance.update'), ['invoice_due_days' => 21])
            ->assertSessionHasErrors('currency');
    }

    public function test_notification_switches_are_saved(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.notifications.update'), [
                'notify_task_assigned' => true,
                'notify_task_commented' => false,
                'notify_task_due_soon' => true,
                'notify_invoice_paid' => true,
                'notify_ticket_created' => false,
                'notify_daily_digest' => false,
            ])
            ->assertRedirect();

        $this->assertTrue(Setting::get('notify_task_assigned'));
        $this->assertFalse(Setting::get('notify_task_commented'));
    }

    public function test_a_plain_member_cannot_change_company_details(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.company.update'), ['company_name' => 'Podmiana'])
            ->assertForbidden();
    }

    public function test_a_custom_field_gets_a_machine_name_from_its_label(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.custom-fields.store'), [
                'model' => 'task',
                'label' => 'Numer zlecenia',
                'type' => 'text',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('custom_fields', ['name' => 'numer_zlecenia', 'model' => 'task']);
    }

    public function test_two_fields_with_the_same_label_do_not_collide(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        foreach ([1, 2] as $ignored) {
            $this->withoutTenantMiddleware()
                ->post(route('tenant.manager.settings.custom-fields.store'), [
                    'model' => 'task',
                    'label' => 'Priorytet klienta',
                    'type' => 'text',
                ]);
        }

        $this->assertSame(
            ['priorytet_klienta', 'priorytet_klienta_2'],
            CustomField::orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_options_are_dropped_for_field_types_that_cannot_use_them(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.custom-fields.store'), [
                'model' => 'task',
                'label' => 'Uwagi',
                'type' => 'text',
                'options' => ['a', 'b'],
            ]);

        $this->assertNull(CustomField::first()->options);
    }

    public function test_connecting_an_integration_stores_its_config(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.integrations.connect'), [
                'type' => 'slack',
                'config' => ['webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX'],
            ])
            ->assertRedirect();

        $integration = Integration::firstWhere('type', 'slack');
        $this->assertTrue($integration->is_active);
        $this->assertSame('https://hooks.slack.com/services/T000/B000/XXX', $integration->config['webhook_url']);
    }

    public function test_an_unknown_integration_type_is_not_found(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.integrations.connect'), [
                'type' => 'myspace',
                'config' => ['webhook_url' => 'https://x.test'],
            ])
            ->assertNotFound();
    }

    public function test_integration_credentials_are_not_sent_to_the_browser(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);
        Integration::create(['type' => 'github', 'config' => ['token' => 'ghp_secret'], 'is_active' => true]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.settings.integrations.index'))
            ->assertDontSee('ghp_secret');
    }

    public function test_a_template_rejects_placeholders_it_does_not_declare(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $template = EmailTemplate::create([
            'name' => 'invoice.sent',
            'label' => 'Faktura',
            'subject' => 'Faktura {{invoice_number}}',
            'body_html' => '<p>{{total}}</p>',
            'variables' => ['invoice_number', 'total'],
        ]);

        $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.email-templates.update', $template), [
                'subject' => 'Faktura {{invoice_number}}',
                'body_html' => '<p>{{total}} dla {{nieistniejaca}}</p>',
            ])
            ->assertStatus(422);
    }

    public function test_a_template_can_be_restored_to_its_default(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $template = EmailTemplate::create([
            'name' => 'invoice.sent',
            'label' => 'Faktura',
            'subject' => 'Zmienione',
            'body_html' => '<p>Zmienione</p>',
            'variables' => ['invoice_number', 'total', 'due_date', 'company'],
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.settings.email-templates.reset', $template))
            ->assertRedirect();

        $this->assertSame(config('mail_templates')['invoice.sent']['subject'], $template->fresh()->subject);
    }

    public function test_role_permissions_read_back_what_was_granted(): void
    {
        // The model queried a `permission` column the table never had, and the
        // middleware swallowed the error, so every role came back with nothing.
        RolePermission::create(['role' => 'manager', 'action' => 'projects.create', 'allowed' => true]);
        RolePermission::create(['role' => 'manager', 'action' => 'invoices.delete', 'allowed' => false]);

        $this->assertTrue(RolePermission::roleHas('manager', 'projects.create'));
        $this->assertFalse(RolePermission::roleHas('manager', 'invoices.delete'));
        $this->assertSame(['projects.create'], RolePermission::permissionsFor('manager'));
        $this->assertSame(['manager' => ['projects.create']], RolePermission::allGrouped());
    }
}
