<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Expense;
use App\Models\Tenant\Project;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

class ExpenseTest extends TenantTestCase
{
    private function makeExpense(User $user, array $attrs = []): Expense
    {
        return Expense::create(array_merge([
            'user_id' => $user->id,
            'amount' => 200.00,
            'currency' => 'PLN',
            'description' => 'Zakup sprzętu testowego',
            'expense_date' => now()->toDateString(),
            'is_billable' => false,
            'is_approved' => false,
        ], $attrs));
    }

    public function test_expenses_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.expenses.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Finance/Expenses/Index'));
    }

    public function test_manager_can_create_expense(): void
    {
        $user = $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expenses.store'), [
                'amount' => 350.00,
                'currency' => 'PLN',
                'description' => 'Bilet kolejowy na konferencję',
                'expense_date' => now()->toDateString(),
                'is_billable' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'description' => 'Bilet kolejowy na konferencję',
        ]);
    }

    public function test_manager_can_update_expense(): void
    {
        $user = $this->actingAsManager();
        $expense = $this->makeExpense($user);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.expenses.update', $expense), [
                'amount' => 500.00,
                'currency' => 'PLN',
                'description' => 'Zaktualizowany wydatek',
                'expense_date' => now()->toDateString(),
                'is_billable' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 500.00]);
    }

    public function test_admin_can_approve_expense(): void
    {
        $admin = $this->actingAsManager(['workspace_role' => 'admin']);
        $user = User::factory()->create(['workspace_role' => 'member']);
        $expense = $this->makeExpense($user, ['is_approved' => false]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expenses.approve', $expense));

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'is_approved' => true,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_expense(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);
        $user = User::factory()->create(['workspace_role' => 'member']);
        $expense = $this->makeExpense($user, ['is_approved' => false]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expenses.reject', $expense));

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'is_approved' => false]);
    }

    public function test_manager_can_delete_expense(): void
    {
        $user = $this->actingAsManager();
        $expense = $this->makeExpense($user);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.expenses.destroy', $expense));

        $response->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_expense_creation_requires_amount(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expenses.store'), [
                'description' => 'Brak kwoty',
                'expense_date' => now()->toDateString(),
                'currency' => 'PLN',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_expense_can_be_linked_to_project(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expenses.store'), [
                'project_id' => $project->id,
                'amount' => 199.00,
                'currency' => 'PLN',
                'description' => 'Expense for project',
                'expense_date' => now()->toDateString(),
                'is_billable' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'project_id' => $project->id,
            'is_billable' => true,
        ]);
    }
}
