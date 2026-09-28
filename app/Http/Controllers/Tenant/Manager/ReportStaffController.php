<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * What each person got done over a period.
 *
 * Completion rate is counted against the tasks assigned to them in the window,
 * so somebody who was handed twenty tasks and closed ten does not read the
 * same as somebody who was handed two and closed one.
 */
class ReportStaffController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManager();

        [$from, $to] = $this->period($request);

        $report = User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($from, $to) {
                $tasks = $user->assignedTasks()
                    ->whereBetween('tasks.created_at', [$from, $to])
                    ->get();

                $completed = $tasks->whereNotNull('completed_at')->count();
                $hours = (float) $user->timeEntries()
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->sum('hours');
                $billable = (float) $user->timeEntries()
                    ->where('is_billable', true)
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->sum('hours');

                return [
                    'user' => $user->only(['id', 'name', 'avatar']),
                    'projects' => $user->projects()->count(),
                    'total_tasks' => $tasks->count(),
                    'completed_tasks' => $completed,
                    'completion_rate' => $tasks->count() > 0
                        ? round($completed / $tasks->count() * 100)
                        : 0,
                    'total_hours' => $hours,
                    'billable_hours' => $billable,
                ];
            })
            ->values();

        return Inertia::render('Tenant/Manager/Reports/StaffReport', [
            'report' => $report,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
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
