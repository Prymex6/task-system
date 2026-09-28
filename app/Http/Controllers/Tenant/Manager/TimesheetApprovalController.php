<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TimesheetApproval;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Weekly timesheets waiting on a manager.
 *
 * Approving one is what makes the hours on it billable, so it is the point
 * at which they stop being editable and start being invoiced.
 */
class TimesheetApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();

        return Inertia::render('Tenant/Manager/HR/TimesheetApprovals', [
            'timesheets' => TimesheetApproval::with(['user', 'approver'])
                ->when(!$this->canApprove($user), fn ($q) => $q->where('user_id', $user->id))
                ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
                ->orderByDesc('week_start')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'canApprove' => $this->canApprove($user),
        ]);
    }

    public function approve(TimesheetApproval $approval)
    {
        $approver = $this->approver();

        if ($approval->status === 'approved') {
            return back()->with('info', __('messages.timesheet_already_approved'));
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        AuditService::log('timesheet_approved', $approval, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with('success', __('messages.timesheet_approved'));
    }

    public function reject(Request $request, TimesheetApproval $approval)
    {
        $approver = $this->approver();

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $approval->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditService::log('timesheet_rejected', $approval, [], ['reason' => $validated['rejection_reason']]);

        return back()->with('success', __('messages.timesheet_rejected'));
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
}
