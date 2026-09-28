<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('plan')->get();

        return Inertia::render('Landlord/Dashboard', [
            'stats' => [
                'total_tenants' => $tenants->count(),
                'active_tenants' => $tenants->where('status', 'active')->count(),
                'trial_tenants' => $tenants->where('status', 'trial')->count(),
                'total_revenue' => $tenants->where('status', 'active')->sum(fn ($t) => $t->plan?->price ?? 0),
                'recent_tenants' => $tenants->sortByDesc('created_at')->take(10)->values(),
            ],
        ]);
    }
}
