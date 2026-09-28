<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use App\Models\Landlord\Tenant;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('plan')->get();

        $stats = [
            'total_tenants' => $tenants->count(),
            'new_tenants_month' => $tenants->where('created_at', '>=', now()->startOfMonth())->count(),
            'active_users' => $tenants->where('status', 'active')->count(),
            'mrr' => $tenants->where('status', 'active')->sum(fn ($t) => $t->plan?->price ?? 0),
            'total_tasks' => 0,
        ];

        $planDistribution = Plan::withCount('tenants')->get()
            ->map(fn ($plan) => ['name' => $plan->name, 'count' => $plan->tenants_count])
            ->values();

        $growthData = collect(range(5, 0))->map(function ($monthsAgo) use ($tenants) {
            $month = now()->subMonths($monthsAgo);

            return [
                'month' => $month->format('Y-m'),
                'label' => $month->translatedFormat('M'),
                'count' => $tenants->filter(fn ($t) => Carbon::parse($t->created_at)->isSameMonth($month) && Carbon::parse($t->created_at)->isSameYear($month))->count(),
            ];
        })->values();

        $recentTenants = $tenants->sortByDesc('updated_at')->take(10)->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'domain' => $t->subdomain,
            'plan' => $t->plan?->name,
            'users_count' => null,
            'tasks_count' => null,
            'last_activity_at' => $t->updated_at,
        ])->values();

        return Inertia::render('Landlord/Statistics/Index', [
            'stats' => $stats,
            'planDistribution' => $planDistribution,
            'growthData' => $growthData,
            'recentTenants' => $recentTenants,
        ]);
    }
}
