<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Automation;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use App\Models\Tenant\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AutomationController extends Controller
{
    public function index()
    {
        $rules = Automation::orderByDesc('created_at')->get();

        return Inertia::render('Tenant/Manager/Automations/Index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:50',
            'trigger_event' => 'required|string|max:100',
            'conditions' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['conditions'] = json_encode($validated['conditions'] ?? []);
        $validated['actions'] = json_encode($validated['actions']);

        Automation::create($validated);

        return redirect()->route('tenant.manager.automations.index')
            ->with('success', __('messages.automation_created'));
    }

    public function update(Request $request, Automation $automation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:50',
            'trigger_event' => 'required|string|max:100',
            'conditions' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['conditions'] = json_encode($validated['conditions'] ?? []);
        $validated['actions'] = json_encode($validated['actions']);

        $automation->update($validated);

        return redirect()->route('tenant.manager.automations.index')
            ->with('success', __('messages.automation_updated'));
    }

    public function destroy(Automation $automation)
    {
        $automation->delete();

        return back()->with('success', __('messages.automation_deleted'));
    }

    public function toggle(Automation $automation)
    {
        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', $automation->is_active ? 'Reguła aktywowana.' : 'Reguła wyłączona.');
    }

    /**
     * The builder needs the things a rule can point at: who to assign to,
     * which status to move a task to, and which webhook to call.
     */
    public function create()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Automations/Create', [
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => TaskStatus::orderBy('order')->get(['id', 'name', 'color']),
            'webhooks' => Webhook::where('is_active', true)->get(['id', 'name', 'url']),
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
