<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Manager\Concerns\ExportsCsv;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * What each person got done over a period.
 *
 * Completion rate is counted against the tasks assigned to them inside the
 * window, so somebody handed twenty tasks who closed ten does not read the
 * same as somebody handed two who closed one.
 */
class ReportStaffController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorizeManager();

        [$from, $to] = $this->period($request);

        return Inertia::render('Tenant/Manager/Reports/StaffReport', [
            'report' => $this->rows($from, $to),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizeManager();

        [$from, $to] = $this->period($request);

        $rows = $this->rows($from, $to)->map(fn (array $row) => [
            $row['user']['name'],
            $row['projects'],
            $row['total_tasks'],
            $row['completed_tasks'],
            $row['completion_rate'] . '%',
            number_format($row['total_hours'], 2, ',', ''),
            number_format($row['billable_hours'], 2, ',', ''),
        ]);

        return $this->streamCsv(
            'staff.csv',
            ['Name', 'Projects', 'Tasks', 'Completed', 'Completion rate', 'Hours', 'Billable hours'],
            $rows,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(Carbon $from, Carbon $to): Collection
    {
        $window = [$from->toDateString(), $to->toDateString()];

        return User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($from, $to, $window) {
                $tasks = $user->assignedTasks()
                    ->whereBetween('tasks.created_at', [$from, $to])
                    ->get();

                $completed = $tasks->whereNotNull('completed_at')->count();

                return [
                    'user' => $user->only(['id', 'name', 'avatar']),
                    'projects' => $user->projects()->count(),
                    'total_tasks' => $tasks->count(),
                    'completed_tasks' => $completed,
                    'completion_rate' => $tasks->count() > 0 ? (int) round($completed / $tasks->count() * 100) : 0,
                    'total_hours' => (float) $user->timeEntries()->whereBetween('date', $window)->sum('hours'),
                    'billable_hours' => (float) $user->timeEntries()
                        ->where('is_billable', true)
                        ->whereBetween('date', $window)
                        ->sum('hours'),
                ];
            })
            ->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    private function authorizeManager(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403);
    }
}
