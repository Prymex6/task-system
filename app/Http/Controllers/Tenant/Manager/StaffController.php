<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('workspace_role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $staff = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Manager/Staff/Index', [
            'staffList' => $staff,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function show(User $staff)
    {
        $staff->load(['projects', 'assignedTasks', 'staffProfile', 'timeEntries' => fn ($q) => $q->latest()->limit(10)]);

        return Inertia::render('Tenant/Manager/Staff/Show', [
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'workspace_role' => 'required|in:admin,manager,member,guest',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|in:pl,en',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Nie pozwól zmienić roli owner
        if ($staff->isOwner() && Auth::guard('tenant')->id() !== $staff->id) {
            return back()->withErrors(['workspace_role' => __('messages.cannot_change_owner_role')]);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $staff->update($validated);
        Log::info('Staff: zaktualizowano dane pracownika', ['user_id' => $staff->id, 'by' => Auth::guard('tenant')->id()]);

        return back()->with('success', __('messages.employee_updated'));
    }

    public function deactivate(User $staff)
    {
        if ($staff->id === Auth::guard('tenant')->id()) {
            return back()->withErrors(['error' => __('messages.cannot_deactivate_self')]);
        }

        if ($staff->isOwner()) {
            return back()->withErrors(['error' => __('messages.cannot_deactivate_owner')]);
        }

        $staff->update(['is_active' => false]);
        Log::info('Staff: dezaktywowano pracownika', ['user_id' => $staff->id, 'by' => Auth::guard('tenant')->id()]);

        return back()->with('success', __('messages.employee_deactivated'));
    }

    public function activate(User $staff)
    {
        $staff->update(['is_active' => true]);
        Log::info('Staff: aktywowano pracownika', ['user_id' => $staff->id, 'by' => Auth::guard('tenant')->id()]);

        return back()->with('success', __('messages.employee_activated'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('tenant')->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'workspace_role' => 'required|in:admin,manager,member,guest',
        ]);

        $validated['password'] = Hash::make(Str::random(32));
        $validated['is_active'] = true;

        $staff = User::create($validated);
        Log::info('Staff: zaproszono pracownika', ['user_id' => $staff->id, 'by' => Auth::guard('tenant')->id()]);

        return back()->with('success', __('messages.invitation_sent'));
    }

    public function destroy(User $staff)
    {
        if ($staff->id === Auth::guard('tenant')->id()) {
            return response()->json(['error' => 'Nie możesz usunąć własnego konta.'], 422);
        }

        if ($staff->isOwner()) {
            return response()->json(['error' => 'Nie można usunąć konta właściciela workspace.'], 422);
        }

        $staff->delete();
        Log::info('Staff: usunięto pracownika', ['user_id' => $staff->id, 'by' => Auth::guard('tenant')->id()]);

        return back()->with('success', __('messages.employee_deleted'));
    }
}
