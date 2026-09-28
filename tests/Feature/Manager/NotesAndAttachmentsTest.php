<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseReport;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAttachment;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

/**
 * Notes pinned to CRM records, files on tasks, and expense claims.
 */
class NotesAndAttachmentsTest extends TenantTestCase
{
    private function taskFor(User $user): Task
    {
        $project = Project::factory()->create(['created_by' => $user->id]);
        $project->members()->attach($user->id, ['project_role' => 'project_manager', 'added_by' => $user->id]);

        return Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
    }

    public function test_a_note_can_be_pinned_to_a_client(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.notes.store'), [
                'notable_type' => 'client',
                'notable_id' => $client->id,
                'body' => 'Rozmowa o przedłużeniu umowy.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $client->notes()->count());
    }

    public function test_the_notable_type_is_resolved_through_a_fixed_map(): void
    {
        // The type arrives from the browser, so a class name must not be honoured.
        $this->actingAsManager();
        $client = Client::factory()->create();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.notes.store'), [
                'notable_type' => 'App\\Models\\Tenant\\User',
                'notable_id' => $client->id,
                'body' => 'Próba',
            ])
            ->assertSessionHasErrors('notable_type');
    }

    public function test_a_note_can_be_pinned_to_a_task(): void
    {
        $user = $this->actingAsManager();
        $task = $this->taskFor($user);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.notes.store'), [
                'notable_type' => 'task',
                'notable_id' => $task->id,
                'body' => 'Do omówienia na standupie.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $task->notes()->count());
    }

    public function test_only_the_author_or_an_admin_can_edit_a_note(): void
    {
        $author = User::factory()->create();
        $client = Client::factory()->create();
        $note = $client->notes()->create(['user_id' => $author->id, 'body' => 'Cudza notatka']);

        $this->actingAsManager(['workspace_role' => 'member']);

        $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.notes.update', $note), ['body' => 'Podmiana'])
            ->assertForbidden();

        $this->assertSame('Cudza notatka', $note->fresh()->body);
    }

    public function test_an_admin_can_delete_somebody_elses_note(): void
    {
        $author = User::factory()->create();
        $client = Client::factory()->create();
        $note = $client->notes()->create(['user_id' => $author->id, 'body' => 'Do sprzątnięcia']);

        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_an_attachment_lands_on_the_private_disk(): void
    {
        Storage::fake('local');
        $user = $this->actingAsManager();
        $task = $this->taskFor($user);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.attachments.store', $task), [
                'file' => UploadedFile::fake()->create('umowa.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $attachment = TaskAttachment::firstWhere('task_id', $task->id);

        $this->assertSame('umowa.pdf', $attachment->name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_deleting_an_attachment_removes_the_stored_file(): void
    {
        Storage::fake('local');
        $user = $this->actingAsManager();
        $task = $this->taskFor($user);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.attachments.store', $task), [
                'file' => UploadedFile::fake()->create('notatka.txt', 5, 'text/plain'),
            ]);

        $attachment = TaskAttachment::firstWhere('task_id', $task->id);
        $path = $attachment->path;

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.attachments.destroy', [$task, $attachment]))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    }

    public function test_approving_an_expense_report_approves_the_spending_inside_it(): void
    {
        $manager = $this->actingAsManager();
        $claimant = User::factory()->create();

        $report = ExpenseReport::create([
            'user_id' => $claimant->id,
            'title' => 'Delegacja',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'total_amount' => 300,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $inside = Expense::create([
            'user_id' => $claimant->id,
            'amount' => 300,
            'description' => 'Hotel',
            'expense_date' => '2026-01-15',
        ]);

        $outside = Expense::create([
            'user_id' => $claimant->id,
            'amount' => 50,
            'description' => 'Poza okresem',
            'expense_date' => '2026-02-15',
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expense-reports.review', $report), ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $report->fresh()->status);
        $this->assertSame($manager->id, $report->fresh()->approved_by);
        $this->assertTrue((bool) $inside->fresh()->is_approved);
        $this->assertFalse((bool) $outside->fresh()->is_approved);
    }

    public function test_rejecting_an_expense_report_requires_a_reason(): void
    {
        $this->actingAsManager();

        $report = ExpenseReport::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Delegacja',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'submitted',
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expense-reports.review', $report), ['decision' => 'rejected'])
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_a_plain_member_cannot_review_a_report(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $report = ExpenseReport::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Delegacja',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'submitted',
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.expense-reports.review', $report), ['decision' => 'approved'])
            ->assertForbidden();
    }
}
