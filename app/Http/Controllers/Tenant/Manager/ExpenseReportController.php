<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Manager\Concerns\ExportsCsv;
use App\Models\Tenant\ExpenseReport;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Expense claims: a period of somebody's spending, submitted as one packet.
 *
 * Approving a report also approves the expenses inside it, which is what
 * moves that spending into the finance figures.
 */
class ExpenseReportController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $user = $this->user();
        $canApprove = $this->canApprove($user);

        return Inertia::render('Tenant/Manager/Finance/Expenses/Reports', [
            'reports' => ExpenseReport::with(['user:id,name', 'approver:id,name'])
                ->when(!$canApprove, fn ($q) => $q->where('user_id', $user->id))
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->orderByDesc('period_end')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'canApprove' => $canApprove,
        ]);
    }

    public function approve(Request $request, ExpenseReport $report)
    {
        $approver = $this->approver();

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:decision,rejected|nullable|string|max:500',
        ]);

        if ($report->status === $validated['decision']) {
            return back()->with('info', __('messages.report_already_reviewed'));
        }

        DB::transaction(function () use ($report, $validated, $approver) {
            $report->update([
                'status' => $validated['decision'],
                'approved_by' => $approver->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);

            // Approving the packet is what marks the spending itself approved.
            $report->expenses()->update([
                'is_approved' => $validated['decision'] === 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });

        AuditService::log('expense_report_' . $validated['decision'], $report, [], [
            'title' => $report->title,
            'total' => $report->total_amount,
        ]);

        return back()->with('success', $validated['decision'] === 'approved'
            ? 'Raport został zatwierdzony.'
            : 'Raport został odrzucony.');
    }

    private function user(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        return $user;
    }

    private function canApprove(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    private function approver(): User
    {
        $user = $this->user();
        abort_unless($this->canApprove($user), 403);

        return $user;
    }

    public function export(Request $request)
    {
        $user = $this->user();
        abort_unless($this->canApprove($user), 403);

        $rows = ExpenseReport::with('user:id,name')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('period_end')
            ->get()
            ->map(fn (ExpenseReport $report) => [
                $report->title,
                $report->user?->name,
                $report->period_start?->toDateString(),
                $report->period_end?->toDateString(),
                number_format((float) $report->total_amount, 2, ',', ''),
                $report->status,
            ]);

        return $this->streamCsv(
            'expense-reports.csv',
            ['Title', 'Employee', 'From', 'To', 'Amount', 'Status'],
            $rows,
        );
    }
}
