<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * The money dashboard: what came in, what is still owed, what went out.
 *
 * Revenue counts what was actually paid rather than what was invoiced, so the
 * profit line cannot be inflated by issuing invoices nobody has settled.
 */
class FinanceController extends Controller
{
    private const MONTHS_CHARTED = 12;

    public function overview()
    {
        $this->authorizeManager();

        $since = Carbon::now()->startOfMonth()->subMonths(self::MONTHS_CHARTED - 1);

        $revenue = (float) Invoice::whereIn('status', ['paid', 'partial'])->sum('paid_amount');
        $costs = (float) Expense::where('is_approved', true)->sum('amount');

        return Inertia::render('Tenant/Manager/Finance/Overview', [
            'stats' => [
                'revenue' => $revenue,
                'profit' => $revenue - $costs,
                'pending' => (float) Invoice::whereIn('status', ['sent', 'partial'])
                    ->sum(DB::raw('total - paid_amount')),
                'overdue' => (float) Invoice::where('status', 'overdue')
                    ->sum(DB::raw('total - paid_amount')),
                'by_month' => $this->byMonth($since),
                'by_client' => $this->byClient(),
            ],
        ]);
    }

    /**
     * @return list<array{month: string, paid: float}>
     */
    private function byMonth(Carbon $since): array
    {
        $paid = Invoice::where('issue_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') as month, SUM(paid_amount) as paid")
            ->groupBy('month')
            ->pluck('paid', 'month');

        // Every month in the window gets a bar, including the quiet ones.
        return collect(range(0, self::MONTHS_CHARTED - 1))
            ->map(function (int $offset) use ($since, $paid) {
                $month = $since->copy()->addMonths($offset)->format('Y-m');

                return ['month' => $month, 'paid' => (float) ($paid[$month] ?? 0)];
            })
            ->all();
    }

    /**
     * @return list<array{client: array{id: int, name: string}, invoiced: float, paid: float}>
     */
    private function byClient(): array
    {
        return Invoice::with('client:id,name')
            ->selectRaw('client_id, SUM(total) as invoiced, SUM(paid_amount) as paid')
            ->whereNotNull('client_id')
            ->groupBy('client_id')
            ->orderByDesc('paid')
            ->limit(10)
            ->get()
            ->map(fn (Invoice $row) => [
                'client' => [
                    'id' => (int) $row->client_id,
                    'name' => $row->client?->name ?? '—',
                ],
                'invoiced' => (float) $row->invoiced,
                'paid' => (float) $row->paid,
            ])
            ->all();
    }

    private function authorizeManager(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403);
    }
}
