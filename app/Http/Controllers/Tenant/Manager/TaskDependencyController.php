<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Services\TaskDependencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskDependencyController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'depends_on_id' => 'required|exists:tasks,id',
            'type' => 'nullable|in:blocks,required_by,related',
        ]);

        $result = TaskDependencyService::add(
            $task->id,
            $request->depends_on_id,
            $request->input('type', 'blocks')
        );

        if (!$result['success']) {
            return back()->withErrors(['dependency' => $result['message']]);
        }

        return back()->with('success', __('messages.dependency_created'));
    }

    public function destroy(Task $task, int $dependencyId)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        TaskDependencyService::remove($dependencyId);

        return back()->with('success', __('messages.dependency_deleted'));
    }

    public function canStart(Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        return response()->json([
            'can_start' => TaskDependencyService::canStart($task->id),
        ]);
    }
}
