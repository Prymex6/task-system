<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Department;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Support desks a ticket can be routed to, each with its own mailbox.
 */
class DepartmentController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/SupportDepartments', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Both are edited in a modal on the list, so there is no separate page.
     */
    public function create()
    {
        return redirect()->route('tenant.manager.support.departments.index');
    }

    public function edit(Department $department)
    {
        return redirect()->route('tenant.manager.support.departments.index');
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = Department::create($validated);

        AuditService::log('support_department_created', $record, [], $record->only(['name', 'email']));

        return back()->with('success', __('messages.department_created'));
    }

    public function update(Request $request, Department $department)
    {
        $this->admin();

        $validated = $request->validate($this->rules($department->id));

        $old = $department->only(['name', 'email']);
        $department->update($validated);

        AuditService::log('support_department_updated', $department, $old, $department->fresh()->only(['name', 'email']));

        return back()->with('success', __('messages.department_updated'));
    }

    public function destroy(Department $department)
    {
        $this->admin();

        $department->delete();

        AuditService::log('support_department_deleted', $department, ['name' => $department->name], []);

        return back()->with('success', __('messages.department_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'name' => 'required|string|max:100|unique:departments,name,' . $ignore,
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|integer|exists:users,id',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
