<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DepartmentHr;
use App\Models\Tenant\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Tenant/Manager/HR/Positions', [
            'positions' => Position::orderBy('name')->get(),
            'departments' => DepartmentHr::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'department_hr_id' => 'nullable|exists:departments_hr,id',
        ]);

        Position::create($data);

        return back()->with('success', __('messages.position_created'));
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'department_hr_id' => 'nullable|exists:departments_hr,id',
        ]);

        $position->update($data);

        return back()->with('success', __('messages.position_updated'));
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return back()->with('success', __('messages.position_deleted'));
    }
}
