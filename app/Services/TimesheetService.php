<?php

namespace App\Services;

use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\TimesheetApproval;
use App\Models\Tenant\User;
use Carbon\Carbon;

class TimesheetService
{
    public static function getWeekEntries(User $user, string $weekStart): array
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $entries = TimeEntry::with(['task.project'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        return [
            'entries' => $entries,
            'total_hours' => $entries->sum('hours'),
            'billable_hours' => $entries->where('is_billable', true)->sum('hours'),
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
        ];
    }

    public static function getTeamTimesheets(string $weekStart): array
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return User::where('is_active', true)
            ->with(['timeEntries' => fn ($q) => $q->whereBetween('date', [$start, $end])])
            ->get()
            ->map(fn ($user) => [
                'user' => $user->only(['id', 'name', 'avatar']),
                'total_hours' => $user->timeEntries->sum('hours'),
                'billable' => $user->timeEntries->where('is_billable', true)->sum('hours'),
                'approval' => TimesheetApproval::where('user_id', $user->id)
                    ->where('week_start', $start->toDateString())
                    ->first(),
            ])->toArray();
    }

    public static function submit(User $user, string $weekStart): TimesheetApproval
    {
        return TimesheetApproval::updateOrCreate(
            ['user_id' => $user->id, 'week_start' => $weekStart],
            ['status' => 'submitted', 'submitted_at' => now()]
        );
    }

    public static function approve(TimesheetApproval $approval, User $approver): void
    {
        $approval->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public static function reject(TimesheetApproval $approval, User $approver, string $reason = ''): void
    {
        $approval->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $reason,
        ]);
    }
}
