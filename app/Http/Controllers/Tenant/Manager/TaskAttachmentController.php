<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAttachment;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Files attached to a task.
 *
 * Uploads land on the private disk and are served through the app, because a
 * tenant's attachments must not be reachable by guessing a public URL.
 */
class TaskAttachmentController extends Controller
{
    private const MAX_KILOBYTES = 10240;

    public function store(Request $request, Task $task)
    {
        $user = $this->authorizeMember($task);

        $request->validate([
            'file' => 'required|file|max:' . self::MAX_KILOBYTES,
        ]);

        $file = $request->file('file');

        $task->attachments()->create([
            'uploaded_by' => $user->id,
            'name' => $file->getClientOriginalName(),
            'path' => $file->store('tasks/' . $task->id, 'local'),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', __('messages.attachment_created'));
    }

    public function destroy(Task $task, TaskAttachment $attachment)
    {
        $user = $this->authorizeMember($task);
        abort_unless($attachment->task_id === $task->id, 404);
        abort_unless($user->isAdmin() || $attachment->uploaded_by === $user->id, 403);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', __('messages.attachment_deleted'));
    }

    private function authorizeMember(Task $task): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        return $user;
    }
}
