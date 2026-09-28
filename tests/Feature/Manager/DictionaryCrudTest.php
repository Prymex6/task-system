<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\ClientGroup;
use App\Models\Tenant\ContractType;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Deal;
use App\Models\Tenant\DealStage;
use App\Models\Tenant\Department;
use App\Models\Tenant\KbCategory;
use App\Models\Tenant\SlaPolicy;
use App\Models\Tenant\TaskLabel;
use App\Models\Tenant\TaxRate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantTestCase;

/**
 * The small tables a workspace is configured with.
 *
 * Every one of these controllers answered "Feature coming soon" on all four
 * verbs, so none of the dictionaries the rest of the application reads from
 * could be edited at all. They share a shape, so the round trip is checked
 * once for all of them, and the rules that are particular to one are checked
 * on their own below.
 */
class DictionaryCrudTest extends TenantTestCase
{
    /**
     * @return array<string, array{string, string, array<string, mixed>, class-string}>
     */
    public static function dictionaries(): array
    {
        return [
            'task labels' => ['tenant.manager.task-labels', 'Tenant/Manager/Settings/TaskLabels',
                ['name' => 'Bug', 'color' => '#ef4444'], TaskLabel::class],
            'tax rates' => ['tenant.manager.tax-rates', 'Tenant/Manager/Settings/TaxRates',
                ['name' => 'VAT 23', 'rate' => 23, 'country' => 'PL'], TaxRate::class],
            'support departments' => ['tenant.manager.support.departments', 'Tenant/Manager/Settings/SupportDepartments',
                ['name' => 'Billing', 'email' => 'billing@example.com'], Department::class],
            'contract types' => ['tenant.manager.contracts.types', 'Tenant/Manager/Settings/ContractTypes',
                ['name' => 'Retainer'], ContractType::class],
            'client groups' => ['tenant.manager.client-groups', 'Tenant/Manager/Settings/ClientGroups',
                ['name' => 'Key accounts', 'color' => '#6366f1'], ClientGroup::class],
            'kb categories' => ['tenant.manager.kb.categories', 'Tenant/Manager/Settings/KbCategories',
                ['name' => 'Getting started', 'order' => 1], KbCategory::class],
            'sla policies' => ['tenant.manager.support.sla-policies', 'Tenant/Manager/Settings/SlaPolicies',
                ['name' => 'Standard', 'priority' => 'medium', 'response_hours' => 8, 'resolution_hours' => 48],
                SlaPolicy::class],
        ];
    }

    #[DataProvider('dictionaries')]
    public function test_the_page_opens(string $route, string $component, array $payload, string $model): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->get(route($route . '.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component($component));
    }

    #[DataProvider('dictionaries')]
    public function test_a_row_is_created_then_renamed_then_removed(string $route, string $component, array $payload, string $model): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route($route . '.store'), $payload)
            ->assertRedirect();

        $record = $model::sole();
        $this->assertSame($payload['name'], $record->name);

        $this->withoutTenantMiddleware()
            ->put(route($route . '.update', $record->id), [...$payload, 'name' => 'Renamed'])
            ->assertRedirect();

        $this->assertSame('Renamed', $record->fresh()->name);

        $this->withoutTenantMiddleware()
            ->delete(route($route . '.destroy', $record->id))
            ->assertRedirect();

        $this->assertSame(0, $model::count());
    }

    #[DataProvider('dictionaries')]
    public function test_a_row_without_a_name_is_refused(string $route, string $component, array $payload, string $model): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route($route . '.store'), [...$payload, 'name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_a_tax_rate_over_a_hundred_percent_is_refused(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tax-rates.store'), ['name' => 'Silly', 'rate' => 120])
            ->assertSessionHasErrors('rate');
    }

    public function test_only_one_tax_rate_stays_the_default(): void
    {
        $this->actingAsManager();
        $first = TaxRate::create(['name' => 'VAT 23', 'rate' => 23, 'is_default' => true]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tax-rates.store'), ['name' => 'VAT 8', 'rate' => 8, 'is_default' => true])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_the_base_currency_is_worth_one_of_itself(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.currencies.store'), [
                'code' => 'eur', 'symbol' => '€', 'name' => 'Euro',
                'rate_to_base' => 4.31, 'is_default' => true,
            ])
            ->assertRedirect();

        $currency = Currency::sole();
        $this->assertSame('EUR', $currency->code);
        $this->assertSame('1.000000', (string) $currency->rate_to_base);
    }

    public function test_the_base_currency_is_not_deleted(): void
    {
        $this->actingAsManager();
        $currency = Currency::create([
            'code' => 'PLN', 'symbol' => 'zł', 'name' => 'Złoty',
            'rate_to_base' => 1, 'is_default' => true,
        ]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.currencies.destroy', $currency->id))
            ->assertRedirect();

        $this->assertNotNull($currency->fresh());
    }

    public function test_a_stage_cannot_be_both_won_and_lost(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.deals.stages.store'), [
                'name' => 'Schrodinger', 'color' => '#111111',
                'win_probability' => 50, 'is_won' => true, 'is_lost' => true,
            ])
            ->assertSessionHasErrors('is_lost');
    }

    public function test_a_stage_holding_deals_is_not_deleted(): void
    {
        $this->actingAsManager();
        $stage = DealStage::create(['name' => 'Negotiation', 'color' => '#111111', 'win_probability' => 50]);
        Deal::create(['deal_stage_id' => $stage->id, 'title' => 'Big one', 'value' => 1000]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.deals.stages.destroy', $stage->id))
            ->assertRedirect();

        $this->assertNotNull($stage->fresh());
    }

    public function test_a_member_who_is_not_an_admin_cannot_edit_a_dictionary(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.task-labels.store'), ['name' => 'Bug', 'color' => '#ef4444'])
            ->assertForbidden();
    }
}
