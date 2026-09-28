<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Project;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Manager/Reports/Index');
    }

    /**
     * Raport czasu pracy (timesheet).
     */
    public function timeReport(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to) : now()->endOfMonth();

        $query = TimeEntry::with(['user', 'task.project'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('project_id')) {
            $query->whereHas('task', fn ($q) => $q->where('project_id', $request->project_id));
        }

        $entries = $query->orderByDesc('date')->get();
        $byUser = $entries->groupBy('user_id')->map(fn ($e) => [
            'name' => $e->first()->user->name,
            'hours' => round($e->sum('hours'), 2),
        ]);
        $byProject = $entries->groupBy(fn ($e) => $e->task?->project_id)->map(fn ($e) => [
            'name' => $e->first()->task?->project?->name ?? 'Brak projektu',
            'hours' => round($e->sum('hours'), 2),
        ]);

        return Inertia::render('Tenant/Manager/Reports/TimeReport', [
            'entries' => $entries,
            'byUser' => $byUser->values(),
            'byProject' => $byProject->values(),
            'total' => round($entries->sum('hours'), 2),
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'user_id' => $request->user_id,
                'project_id' => $request->project_id,
            ],
            'staff' => $user->isAdmin() ? User::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    /**
     * Raport finansowy.
     */
    public function financeReport(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $year = $request->filled('year') ? (int) $request->year : now()->year;

        // Miesięczne przychody (opłacone faktury)
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereYear('paid_at', $year)
            ->select(DB::raw('MONTH(paid_at) as month'), DB::raw('SUM(total) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        $revenueByMonth = array_map(fn ($m) => [
            'month' => $m,
            'label' => Carbon::create($year, $m)->format('M'),
            'revenue' => (float) ($monthlyRevenue[$m] ?? 0),
        ], range(1, 12));

        $summary = [
            'total_invoiced' => Invoice::whereYear('issue_date', $year)->sum('total'),
            'total_paid' => Invoice::where('status', 'paid')->whereYear('paid_at', $year)->sum('total'),
            'total_overdue' => Invoice::overdue()->sum('balance_due'),
            'total_expenses' => Expense::whereYear('date', $year)->sum('amount'),
        ];

        return Inertia::render('Tenant/Manager/Reports/FinanceReport', [
            'revenueByMonth' => $revenueByMonth,
            'summary' => $summary,
            'year' => $year,
        ]);
    }

    /**
     * Raport projektów.
     */
    public function projectReport(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $projects = Project::withCount([
            'tasks',
            'tasks as completed_tasks' => fn ($q) => $q->where('is_completed', true),
        ])
            ->with('client')
            ->where('is_archived', false)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->client?->company_name ?? $p->client?->name,
                'status' => $p->status,
                'tasks' => $p->tasks_count,
                'completed' => $p->completed_tasks,
                'progress' => $p->tasks_count > 0 ? round(($p->completed_tasks / $p->tasks_count) * 100) : 0,
                'due_date' => $p->due_date,
                'is_overdue' => $p->due_date && new Carbon($p->due_date) < now() && $p->status !== 'completed',
            ]);

        return Inertia::render('Tenant/Manager/Reports/ProjectReport', [
            'projects' => $projects,
        ]);
    }
}
