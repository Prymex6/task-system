<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Holiday;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $year = $request->input('year', now()->year);
        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->get();

        return Inertia::render('Tenant/Manager/HR/Holidays', [
            'holidays' => $holidays,
            'year' => $year,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_paid' => 'boolean',
            'is_recurring' => 'boolean',
        ]);

        $holiday = Holiday::create($request->only(['name', 'date', 'is_paid', 'is_recurring']));

        AuditService::log('holiday_created', $holiday, [], $holiday->toArray());

        return back()->with('success', __('messages.holiday_created'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_paid' => 'boolean',
            'is_recurring' => 'boolean',
        ]);

        $old = $holiday->toArray();
        $holiday->update($request->only(['name', 'date', 'is_paid', 'is_recurring']));

        AuditService::log('holiday_updated', $holiday, $old, $holiday->fresh()->toArray());

        return back()->with('success', __('messages.holiday_updated'));
    }

    public function destroy(Holiday $holiday)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $holiday->delete();

        return back()->with('success', __('messages.holiday_deleted'));
    }
}
