<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DepartmentHr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentHrController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Tenant/Manager/HR/Departments', [
            'departments' => DepartmentHr::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('tenant.manager.departments.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:1000',
        ]);

        DepartmentHr::create($data);

        return back()->with('success', __('messages.department_created'));
    }

    public function edit(DepartmentHr $department)
    {
        return redirect()->route('tenant.manager.departments.index');
    }

    public function update(Request $request, DepartmentHr $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:1000',
        ]);

        $department->update($data);

        return back()->with('success', __('messages.department_updated'));
    }

    public function destroy(DepartmentHr $department)
    {
        $department->delete();

        return back()->with('success', __('messages.department_deleted'));
    }
}
