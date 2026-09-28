<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ProjectTemplate;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskTemplateController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $templates = ProjectTemplate::withCount('tasks')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Tenant/Manager/Tasks/Templates', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'required|string',
            'tasks.*.priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $template = ProjectTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => $user->id,
        ]);

        foreach ($request->input('tasks', []) as $i => $taskData) {
            $template->tasks()->create([
                'title' => $taskData['title'],
                'priority' => $taskData['priority'] ?? 'medium',
                'order' => $i,
            ]);
        }

        AuditService::log('template_created', $template, [], $template->toArray());

        return back()->with('success', __('messages.template_created'));
    }

    public function update(Request $request, ProjectTemplate $template)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $old = $template->only(['name', 'description']);
        $template->update($request->only(['name', 'description']));

        AuditService::log('template_updated', $template, $old, $template->fresh()->only(['name', 'description']));

        return back()->with('success', __('messages.template_updated'));
    }

    public function destroy(ProjectTemplate $template)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $template->delete();

        return back()->with('success', __('messages.template_deleted'));
    }
}
