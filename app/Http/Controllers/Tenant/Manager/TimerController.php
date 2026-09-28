<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\Timer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimerController extends Controller
{
    /**
     * The timer this person currently has running.
     */
    public function current()
    {
        $user = Auth::guard('tenant')->user();
        $timer = Timer::where('user_id', $user->id)->with('task')->first();

        return response()->json(['timer' => $timer]);
    }

    /**
     * Startuje timer dla zadania (lub kontynuuje).
     */
    public function start(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'description' => 'nullable|string|max:500',
        ]);

        // Zatrzymaj ewentualny aktywny timer
        $existing = Timer::where('user_id', $user->id)->first();
        if ($existing) {
            $this->stopAndSave($existing, $user);
        }

        Timer::create([
            'user_id' => $user->id,
            'task_id' => $validated['task_id'],
            'description' => $validated['description'] ?? null,
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'started_at' => now()]);
    }

    /**
     * Zatrzymuje aktywny timer i zapisuje wpis czasu.
     */
    public function stop(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $timer = Timer::where('user_id', $user->id)->first();

        if (!$timer) {
            return response()->json(['error' => 'Brak aktywnego timera.'], 422);
        }

        $entry = $this->stopAndSave($timer, $user);

        return response()->json([
            'success' => true,
            'entry' => $entry,
        ]);
    }

    /**
     * Adds a time entry by hand rather than from a timer.
     */
    public function storeEntry(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'date' => 'required|date|before_or_equal:today',
            'hours' => 'required|numeric|min:0.1|max:24',
            'description' => 'nullable|string|max:500',
            'is_billable' => 'boolean',
        ]);

        $entry = TimeEntry::create(array_merge($validated, ['user_id' => $user->id]));

        return back()->with('success', __('messages.time_logged'));
    }

    /**
     * Usuwa wpis czasu.
     */
    public function destroyEntry(TimeEntry $entry)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $entry->user_id === $user->id, 403);

        $entry->delete();

        return back()->with('success', __('messages.time_entry_deleted'));
    }

    /**
     * The time entries behind a timesheet.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = TimeEntry::with(['user', 'task.project'])
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id));

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        $entries = $query->orderByDesc('date')->paginate(30)->withQueryString();
        $totalHours = $query->sum('hours');

        return Inertia::render('Tenant/Manager/Time/Index', [
            'entries' => $entries,
            'totalHours' => (float) $totalHours,
            'filters' => $request->only(['date_from', 'date_to', 'task_id', 'user_id']),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function stopAndSave(Timer $timer, $user): TimeEntry
    {
        $seconds = $timer->total_seconds;
        $hours = round($seconds / 3600, 2);

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'task_id' => $timer->task_id,
            'date' => Carbon::parse($timer->started_at)->toDateString(),
            'hours' => max(0.01, $hours),
            'description' => $timer->description,
            'is_billable' => true,
        ]);

        $timer->delete();

        return $entry;
    }
}
