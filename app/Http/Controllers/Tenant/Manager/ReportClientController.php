<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Each client as a single line: what they are worth, what they still owe, and
 * how much of the team is currently pointed at them.
 */
class ReportClientController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManager();

        $report = Client::with('projects')
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $invoiced = (float) $client->invoices()->sum('total');
                $paid = (float) $client->invoices()->sum('paid_amount');

                return [
                    'client' => $client->only(['id', 'name', 'company_name']),
                    'total_projects' => $client->projects->count(),
                    'open_projects' => $client->projects->where('is_archived', false)->count(),
                    'deals' => Deal::where('client_id', $client->id)->count(),
                    'open_tickets' => $client->tickets()
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->count(),
                    'total_invoiced' => $invoiced,
                    'total_paid' => $paid,
                    'outstanding' => round($invoiced - $paid, 2),
                ];
            })
            ->sortByDesc('total_invoiced')
            ->values();

        return Inertia::render('Tenant/Manager/Reports/ClientsReport', [
            'report' => $report,
            'filters' => $request->only('search'),
        ]);
    }

    private function authorizeManager(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403);
    }
}
