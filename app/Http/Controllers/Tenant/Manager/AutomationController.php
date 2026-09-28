<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Automation;
use Illuminate\Http\Request;
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
}
