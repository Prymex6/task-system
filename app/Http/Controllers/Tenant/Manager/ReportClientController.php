<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Manager\Concerns\ExportsCsv;
use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Each client as a single line: what they are worth, what they still owe, and
 * how much of the team is currently pointed at them.
 *
 * The screen and the CSV are built from the same rows, so an export can never
 * disagree with what was on screen when somebody clicked it.
 */
class ReportClientController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Reports/ClientsReport', [
            'report' => $this->rows(),
            'filters' => $request->only('search'),
        ]);
    }

    public function export()
    {
        $this->authorizeManager();

        $rows = $this->rows()->map(fn (array $row) => [
            $row['client']['company_name'] ?: $row['client']['name'],
            $row['total_projects'],
            $row['open_projects'],
            $row['deals'],
            $row['open_tickets'],
            number_format($row['total_invoiced'], 2, ',', ''),
            number_format($row['total_paid'], 2, ',', ''),
            number_format($row['outstanding'], 2, ',', ''),
        ]);

        return $this->streamCsv(
            'clients.csv',
            ['Client', 'Projects', 'Open projects', 'Deals', 'Open tickets', 'Invoiced', 'Paid', 'Outstanding'],
            $rows,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(): Collection
    {
        return Client::with('projects')
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
                    'open_tickets' => $client->tickets()->whereNotIn('status', ['resolved', 'closed'])->count(),
                    'total_invoiced' => $invoiced,
                    'total_paid' => $paid,
                    'outstanding' => round($invoiced - $paid, 2),
                ];
            })
            ->sortByDesc('total_invoiced')
            ->values();
    }

    private function authorizeManager(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403);
    }
}
