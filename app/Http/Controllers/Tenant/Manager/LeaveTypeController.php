<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Tenant/Manager/HR/LeaveTypes', [
            'leaveTypes' => LeaveType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'requires_approval' => 'boolean',
            'days_per_year' => 'nullable|integer|min:0',
        ]);

        LeaveType::create($data);

        return back()->with('success', __('messages.leave_type_created'));
    }

    public function update(Request $request, LeaveType $leave_type)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'requires_approval' => 'boolean',
            'days_per_year' => 'nullable|integer|min:0',
        ]);

        $leave_type->update($data);

        return back()->with('success', __('messages.leave_type_updated'));
    }

    public function destroy(LeaveType $leave_type)
    {
        $leave_type->delete();

        return back()->with('success', __('messages.leave_type_deleted'));
    }
}
