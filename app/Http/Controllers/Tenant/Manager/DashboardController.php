<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Project;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Task;
use App\Models\Tenant\Ticket;
use App\Models\Tenant\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $tz = Setting::get('timezone', 'Europe/Warsaw');

        $todayStart = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $weekStart = Carbon::now($tz)->startOfWeek()->setTimezone('UTC');

        // === Projekty ===
        $projectsQuery = $user->isAdmin()
            ? Project::query()
            : Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id));

        $activeProjects = (clone $projectsQuery)->where('status', 'in_progress')->count();
        $totalProjects = (clone $projectsQuery)->count();
        $overdueProjects = (clone $projectsQuery)->where('due_date', '<', now())->where('status', '!=', 'completed')->count();

        // === Zadania ===
        $myTasks = Task::whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))->whereNull('completed_at')->count();
        $overdueTasks = Task::whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();
        $dueTodayTasks = Task::whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))
            ->whereNull('completed_at')
            ->whereDate('due_date', Carbon::now($tz)->toDateString())
            ->count();

        // === Finanse ===
        $pendingInvoices = Invoice::where('status', 'sent')->sum('total');
        $overdueInvoices = Invoice::overdue()->sum('total');
        $thisMonthRevenue = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $monthStart)
            ->sum('total');

        // === Klienci ===
        $totalClients = Client::where('is_active', true)->count();
        $newThisMonth = Client::where('created_at', '>=', $monthStart)->count();

        // === Czas pracy ===
        $myHoursToday = TimeEntry::where('user_id', $user->id)
            ->where('date', Carbon::now($tz)->toDateString())
            ->sum('hours');
        $myHoursWeek = TimeEntry::where('user_id', $user->id)
            ->where('date', '>=', Carbon::now($tz)->startOfWeek()->toDateString())
            ->sum('hours');

        // === Tickety support ===
        $openTickets = Ticket::whereIn('status', ['open', 'pending'])->count();
        $urgentTickets = Ticket::where('priority', 'urgent')->whereIn('status', ['open', 'pending'])->count();

        // === Moje ostatnie projekty ===
        $myProjects = (clone $projectsQuery)
            ->with(['client', 'members'])
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'due_date' => $p->due_date?->format('Y-m-d'),
                'progress' => $p->progress,
                'client' => $p->client?->getDisplayNameAttribute(),
            ]);

        // === Moje zadania (nadchodzące) ===
        $upcomingTasks = Task::with(['project', 'status'])
            ->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))
            ->whereNull('completed_at')
            ->orderByRaw('ISNULL(due_date), due_date ASC')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'priority' => $t->priority,
                'project' => $t->project?->name,
                'project_id' => $t->project_id,
                'is_overdue' => $t->isOverdue(),
            ]);

        // === Aktywność w projekcie (ostatnie 7 dni) ===
        $activityChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now($tz)->subDays($i)->toDateString();
            $activityChart[] = [
                'date' => $day,
                'label' => Carbon::parse($day)->format('d.m'),
                'tasks_completed' => Task::whereNotNull('completed_at')
                    ->whereDate('completed_at', $day)
                    ->count(),
                'time_logged' => TimeEntry::where('date', $day)->sum('hours'),
            ];
        }

        return Inertia::render('Tenant/Manager/Dashboard', [
            'stats' => [
                'projects' => [
                    'active' => $activeProjects,
                    'total' => $totalProjects,
                    'overdue' => $overdueProjects,
                ],
                'tasks' => [
                    'my_open' => $myTasks,
                    'overdue' => $overdueTasks,
                    'due_today' => $dueTodayTasks,
                ],
                'finance' => [
                    'pending_invoices' => (float) $pendingInvoices,
                    'overdue_invoices' => (float) $overdueInvoices,
                    'month_revenue' => (float) $thisMonthRevenue,
                ],
                'clients' => [
                    'total' => $totalClients,
                    'new_month' => $newThisMonth,
                ],
                'time' => [
                    'today' => (float) $myHoursToday,
                    'week' => (float) $myHoursWeek,
                ],
                'tickets' => [
                    'open' => $openTickets,
                    'urgent' => $urgentTickets,
                ],
            ],
            'myProjects' => $myProjects,
            'upcomingTasks' => $upcomingTasks,
            'activityChart' => $activityChart,
        ]);
    }
}
