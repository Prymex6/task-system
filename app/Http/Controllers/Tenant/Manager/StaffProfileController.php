<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffProfileController extends Controller
{
    public function show(User $user)
    {
        $currentUser = Auth::guard('tenant')->user();
        abort_unless($currentUser?->isAdmin() || $currentUser?->id === $user->id, 403);

        $user->load(['timesheets' => fn ($q) => $q->latest()->limit(10), 'assignedTasks' => fn ($q) => $q->with('project', 'status')->limit(10)]);

        return Inertia::render('Tenant/Manager/HR/StaffProfile', [
            'staff' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::guard('tenant')->user();
        abort_unless($currentUser?->isAdmin() || $currentUser?->id === $user->id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
        ]);

        $old = $user->only(['name', 'email', 'phone', 'position', 'timezone']);
        $user->update($request->only(['name', 'email', 'phone', 'position', 'timezone']));

        AuditService::log('staff_updated', $user, $old, $user->fresh()->only(['name', 'email', 'phone', 'position', 'timezone']));

        return back()->with('success', __('messages.profile_updated'));
    }

    public function updateRole(Request $request, User $user)
    {
        $currentUser = Auth::guard('tenant')->user();
        abort_unless($currentUser?->isAdmin(), 403);

        $request->validate(['workspace_role' => 'required|in:owner,admin,manager,member,guest']);

        $oldRole = $user->workspace_role;
        $user->update(['workspace_role' => $request->workspace_role]);

        AuditService::log('staff_role_changed', $user, ['workspace_role' => $oldRole], ['workspace_role' => $request->workspace_role]);

        return back()->with('success', __('messages.role_updated'));
    }

    public function resetPassword(Request $request, User $user)
    {
        $currentUser = Auth::guard('tenant')->user();
        abort_unless($currentUser?->isAdmin(), 403);

        $request->validate(['password' => 'required|min:8|confirmed']);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', __('messages.password_reset'));
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::guard('tenant')->user();
        abort_unless($currentUser?->isAdmin() && $currentUser->id !== $user->id, 403);

        $user->delete();

        AuditService::log('staff_deleted', $user, $user->toArray(), []);

        return redirect()->route('tenant.manager.staff.index')->with('success', __('messages.employee_deleted'));
    }
}
