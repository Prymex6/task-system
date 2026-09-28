<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HrController extends Controller
{
    // ── Attendance ────────────────────────────────────────────────────────────

    public function attendance(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = Attendance::with('user');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        $month = $request->filled('month') ? $request->month : now()->format('Y-m');
        $query->where('date', 'like', $month . '%');

        $records = $query->orderByDesc('date')->paginate(31)->withQueryString();

        return Inertia::render('Tenant/Manager/HR/Attendance', [
            'records' => $records,
            'staff' => $user->isAdmin() ? User::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'month' => $month,
            'filters' => $request->only(['user_id', 'month']),
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $today = now()->toDateString();

        $existing = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing?->clock_in) {
            return back()->withErrors(['error' => __('messages.already_clocked_in')]);
        }

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => now()->toTimeString()]
        );

        return back()->with('success', __('messages.clocked_in_at', ['time' => now()->format('H:i')]));
    }

    public function clockOut(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $today = now()->toDateString();
        $record = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        if (!$record || !$record->clock_in) {
            return back()->withErrors(['error' => __('messages.clock_in_first')]);
        }

        if ($record->clock_out) {
            return back()->withErrors(['error' => __('messages.already_clocked_out')]);
        }

        $clockIn = Carbon::parse($today . ' ' . $record->clock_in);
        $clockOut = now();
        $hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);

        $record->update([
            'clock_out' => $clockOut->toTimeString(),
            'total_hours' => $hours,
        ]);

        return back()->with('success', __('messages.clocked_out_at', ['time' => $clockOut->format('H:i') . ' (' . $hours . 'h)']));
    }

    // ── Urlopy ────────────────────────────────────────────────────────────────

    public function leave(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = LeaveRequest::with(['user', 'leaveType']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();
        $types = LeaveType::where('is_active', true)->get();
        $balances = LeaveBalance::where('user_id', $user->id)
            ->with('leaveType')
            ->where('year', now()->year)
            ->get();

        return Inertia::render('Tenant/Manager/HR/Leave', [
            'requests' => $requests,
            'types' => $types,
            'balances' => $balances,
            'filters' => $request->only(['status', 'user_id']),
        ]);
    }

    public function requestLeave(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Policz dni robocze
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $days = 0;
        $holidays = Holiday::whereBetween('date', [$start, $end])->pluck('date')->map(fn ($d) => $d->toDateString());

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend() && !$holidays->contains($date->toDateString())) {
                $days++;
            }
        }

        LeaveRequest::create(array_merge($validated, [
            'user_id' => $user->id,
            'days' => $days,
            'status' => 'pending',
        ]));

        return back()->with('success', __('messages.leave_requested'));
    }

    public function approveLeave(LeaveRequest $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $request->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);

        // Zaktualizuj bilans urlopowy
        $balance = LeaveBalance::where('user_id', $request->user_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', Carbon::parse($request->start_date)->year)
            ->first();

        if ($balance) {
            $balance->increment('used', $request->days);
        }

        return back()->with('success', __('messages.leave_approved'));
    }

    public function rejectLeave(Request $request, LeaveRequest $leave)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $leave->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
        ]);

        return back()->with('success', __('messages.leave_rejected'));
    }
}
