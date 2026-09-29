<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use App\Models\Landlord\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Workspaces, from the platform's side.
 *
 * Creating one provisions a database, so the work happens through the tenancy
 * package rather than a plain insert. Deleting one drops that database, which
 * is why it asks for the workspace name to be typed back.
 */
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with('plan:id,name')
            ->when($request->query('search'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Landlord/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Landlord/Tenants/Create', [
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(['id', 'name', 'price']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:63|unique:central.tenants,subdomain|regex:/^[a-z0-9-]+$/',
            'plan_id' => 'nullable|integer|exists:central.plans,id',
            'trial_ends_at' => 'nullable|date',
            'manager_email' => 'nullable|email|max:255',
        ]);

        // The package creates and migrates the workspace database off the back
        // of this, so there is nothing to seed by hand afterwards.
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'subdomain' => $validated['subdomain'],
            'plan_id' => $validated['plan_id'] ?? null,
            'status' => 'trial',
            'trial_ends_at' => $validated['trial_ends_at'] ?? now()->addWeeks(3),
        ]);

        $tenant->domains()->create(['domain' => $validated['subdomain']]);

        return redirect()->route('landlord.tenants.show', $tenant)
            ->with('success', __('messages.workspace_created'));
    }

    public function show(Tenant $tenant)
    {
        return Inertia::render('Landlord/Tenants/Show', [
            'tenant' => $tenant->load('plan'),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(['id', 'name', 'price']),
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return Inertia::render('Landlord/Tenants/Edit', [
            'tenant' => $tenant->load('plan'),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(['id', 'name', 'price']),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_id' => 'nullable|integer|exists:central.plans,id',
            'trial_ends_at' => 'nullable|date',
        ]);

        $tenant->update($validated);

        return back()->with('success', __('messages.workspace_updated'));
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        return back()->with('success', __('messages.workspace_suspended'));
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);

        return back()->with('success', __('messages.workspace_activated'));
    }

    /**
     * Deleting a workspace drops its database, so the name has to be typed
     * back before anything happens.
     */
    public function destroy(Request $request, Tenant $tenant)
    {
        $request->validate(['confirm' => 'required|string']);

        if ($request->input('confirm') !== $tenant->name) {
            return back()->withErrors(['confirm' => __('messages.workspace_name_does_not_match')]);
        }

        $tenant->delete();

        return redirect()->route('landlord.tenants.index')
            ->with('success', __('messages.workspace_deleted'));
    }

    /**
     * Open a workspace as one of its own users.
     *
     * Support cannot see what a customer sees from the outside, so this
     * exists. It is written to the log on the way through, because signing in
     * as somebody else is the kind of thing that should leave a trace.
     */
    public function impersonate(Tenant $tenant)
    {
        abort_unless($tenant->status !== 'suspended', 403, __('messages.workspace_suspended_cannot_open'));

        $domain = $tenant->domains()->value('domain') ?? $tenant->subdomain;
        abort_unless($domain, 404, __('messages.workspace_has_no_domain'));

        Log::warning('Super admin opened a workspace', [
            'tenant' => $tenant->id,
            'admin' => auth('super_admin')->id(),
        ]);

        return redirect()->away('https://' . $domain);
    }
}
