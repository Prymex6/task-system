<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * The workspace's own subscription, as opposed to the invoices it issues.
 *
 * Plans and the tenant record live in the landlord database, so this is the
 * one manager screen that reads across the tenancy boundary.
 */
class BillingController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $tenant = tenant();

        return Inertia::render('Tenant/Manager/Settings/Billing', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan_id' => $tenant->plan_id,
                'plan' => $tenant->plan,
                'trial_ends_at' => $tenant->trial_ends_at ?? null,
            ] : null,
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'invoices' => [],
        ]);
    }

    public function changePlan(Request $request)
    {
        $this->authorizeAdmin();

        $tenant = tenant();
        abort_unless($tenant, 404);

        $validated = $request->validate([
            'plan_id' => 'required|integer',
        ]);

        $plan = Plan::where('is_active', true)->findOrFail($validated['plan_id']);

        if ($tenant->plan_id === $plan->id) {
            return back()->with('info', __('messages.plan_already_active'));
        }

        $previous = $tenant->plan_id;
        $tenant->update(['plan_id' => $plan->id]);

        AuditService::log('plan_changed', null, ['plan_id' => $previous], ['plan_id' => $plan->id]);

        return back()->with('success', __('messages.plan_changed_to', ['plan' => $plan->name]));
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
