<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $year = $request->input('year', now()->year);
        $balances = LeaveBalance::with('user')
            ->where('year', $year)
            ->orderBy('created_at')
            ->paginate(20);

        $requests = LeaveRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Tenant/Manager/HR/LeaveBalances', [
            'balances' => $balances,
            'pendingRequests' => $requests,
            'year' => $year,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2000',
            'leave_type' => 'required|string|max:100',
            'total_days' => 'required|integer|min:0',
        ]);

        $balance = LeaveBalance::updateOrCreate(
            ['user_id' => $request->user_id, 'year' => $request->year, 'leave_type' => $request->leave_type],
            ['total_days' => $request->total_days]
        );

        AuditService::log('leave_balance_set', $balance, [], $balance->toArray());

        return back()->with('success', __('messages.leave_balance_saved'));
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $leaveRequest->update(['status' => 'approved', 'approved_by' => $user->id]);

        AuditService::log('leave_approved', $leaveRequest, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with('success', __('messages.leave_approved'));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $leaveRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        AuditService::log('leave_rejected', $leaveRequest, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with('success', __('messages.leave_rejected'));
    }
}
