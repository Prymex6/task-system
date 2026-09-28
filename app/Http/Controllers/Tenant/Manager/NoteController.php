<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Lead;
use App\Models\Tenant\Note;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Free-text notes pinned to a client, lead, deal, project or task.
 *
 * The type arrives from the browser, so it is resolved through a fixed map
 * rather than trusted as a class name.
 */
class NoteController extends Controller
{
    private const NOTABLE = [
        'client' => Client::class,
        'lead' => Lead::class,
        'deal' => Deal::class,
        'project' => Project::class,
        'task' => Task::class,
    ];

    public function store(Request $request)
    {
        $user = $this->user();

        $validated = $request->validate([
            'notable_type' => 'required|string|in:' . implode(',', array_keys(self::NOTABLE)),
            'notable_id' => 'required|integer',
            'body' => 'required|string|max:5000',
        ]);

        $model = self::NOTABLE[$validated['notable_type']];
        $subject = $model::findOrFail($validated['notable_id']);

        $subject->notes()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', __('messages.note_created'));
    }

    public function update(Request $request, Note $note)
    {
        $this->authorizeOwner($note);

        $note->update($request->validate(['body' => 'required|string|max:5000']));

        return back()->with('success', __('messages.note_updated'));
    }

    public function destroy(Note $note)
    {
        $this->authorizeOwner($note);

        $note->delete();

        return back()->with('success', __('messages.note_deleted'));
    }

    private function user(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        return $user;
    }

    /**
     * A note is editable by whoever wrote it, or by an admin cleaning up.
     */
    private function authorizeOwner(Note $note): void
    {
        $user = $this->user();
        abort_unless($user->isAdmin() || $note->user_id === $user->id, 403);
    }
}
