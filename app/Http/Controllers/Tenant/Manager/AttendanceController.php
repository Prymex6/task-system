<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $today = now()->toDateString();
        $active = Attendance::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->latest()
            ->first();

        $records = Attendance::with('user')
            ->where('user_id', $user->id)
            ->latest('clock_in')
            ->limit(30)
            ->get();

        return Inertia::render('Tenant/Manager/HR/Attendance', [
            'active' => $active,
            'records' => $records,
        ]);
    }

    public function reports(Request $request)
    {
        return Inertia::render('Tenant/Manager/HR/Attendance', [
            'active' => null,
            'records' => [],
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        Attendance::where('user_id', $user->id)->whereNull('clock_out')->update(['clock_out' => now()]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'notes' => $request->note,
        ]);

        return back()->with('success', __('messages.clocked_in'));
    }

    public function clockOut(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $active = Attendance::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();

        if ($active) {
            $active->update(['clock_out' => now()]);
        }

        return back()->with('success', __('messages.clocked_out'));
    }
}
