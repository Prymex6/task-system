<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TaskLabel;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Labels a task can be tagged with, shown as chips on the board.
 */
class TaskLabelController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/TaskLabels', [
            'labels' => TaskLabel::withCount('tasks')->orderBy('name')->get(),
        ]);
    }

    /**
     * Both are edited in a modal on the list, so there is no separate page.
     */
    public function create()
    {
        return redirect()->route('tenant.manager.task-labels.index');
    }

    public function edit(TaskLabel $taskLabel)
    {
        return redirect()->route('tenant.manager.task-labels.index');
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = TaskLabel::create($validated);

        AuditService::log('task_label_created', $record, [], $record->only(['name', 'color']));

        return back()->with('success', __('messages.label_created'));
    }

    public function update(Request $request, TaskLabel $taskLabel)
    {
        $this->admin();

        $validated = $request->validate($this->rules($taskLabel->id));

        $old = $taskLabel->only(['name', 'color']);
        $taskLabel->update($validated);

        AuditService::log('task_label_updated', $taskLabel, $old, $taskLabel->fresh()->only(['name', 'color']));

        return back()->with('success', __('messages.label_updated'));
    }

    public function destroy(TaskLabel $taskLabel)
    {
        $this->admin();

        $taskLabel->delete();

        AuditService::log('task_label_deleted', $taskLabel, ['name' => $taskLabel->name], []);

        return back()->with('success', __('messages.label_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'name' => 'required|string|max:50|unique:task_labels,name,' . $ignore,
            'color' => 'required|string|max:7',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
