<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Time off: asking for it, and the manager answering.
 *
 * A day is only taken off somebody's balance once it is approved, and put
 * back if the approval is reversed, so a request sitting in the queue never
 * eats into what is left.
 */
class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();

        $leaves = LeaveRequest::with(['user', 'leaveType', 'approver'])
            ->when(!$user->isAdmin() && !$user->isManager(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->orderByDesc('start_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/HR/Leave', [
            'leaves' => $leaves,
            'myBalance' => LeaveBalance::with('leaveType')
                ->where('user_id', $user->id)
                ->where('year', now()->year)
                ->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'filters' => $request->only(['status', 'user_id']),
            'canApprove' => $user->isAdmin() || $user->isManager(),
        ]);
    }

    public function calendar(Request $request)
    {
        $this->user();

        $month = Carbon::parse($request->query('month', now()->toDateString()));

        return Inertia::render('Tenant/Manager/HR/LeaveCalendar', [
            'month' => $month->toDateString(),
            'leaves' => LeaveRequest::with(['user', 'leaveType'])
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $month->copy()->endOfMonth())
                ->whereDate('end_date', '>=', $month->copy()->startOfMonth())
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();

        $validated = $request->validate([
            'leave_type' => 'required|integer|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $validated['leave_type'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => $this->workingDaysBetween($start, $end),
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        AuditService::log('leave_requested', $leave, [], $leave->only(['start_date', 'end_date', 'days_count']));

        return back()->with('success', __('messages.leave_requested'));
    }

    public function approve(LeaveRequest $request)
    {
        $approver = $this->approver();

        if ($request->status === 'approved') {
            return back()->with('info', __('messages.request_already_approved'));
        }

        $request->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->moveBalance($request, $request->days_count);

        AuditService::log('leave_approved', $request, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with('success', __('messages.request_approved'));
    }

    public function reject(Request $httpRequest, LeaveRequest $request)
    {
        $approver = $this->approver();

        $validated = $httpRequest->validate(['rejection_reason' => 'nullable|string|max:500']);

        // Days already taken off a balance go back when an approval is undone.
        if ($request->status === 'approved') {
            $this->moveBalance($request, -$request->days_count);
        }

        $request->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        AuditService::log('leave_rejected', $request, [], ['reason' => $request->rejection_reason]);

        return back()->with('success', __('messages.request_rejected'));
    }

    /**
     * Weekends do not count against an allowance.
     */
    private function workingDaysBetween(Carbon $start, Carbon $end): int
    {
        $days = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    private function moveBalance(LeaveRequest $leave, int $days): void
    {
        $balance = LeaveBalance::firstOrNew([
            'user_id' => $leave->user_id,
            'leave_type_id' => $leave->leave_type_id,
            'year' => Carbon::parse($leave->start_date)->year,
        ]);

        $balance->available_days ??= 0;
        $balance->used_days = max(0, (int) $balance->used_days + $days);
        $balance->save();
    }

    private function user(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        return $user;
    }

    private function approver(): User
    {
        $user = $this->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        return $user;
    }
}
