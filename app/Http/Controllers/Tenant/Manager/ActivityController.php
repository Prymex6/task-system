<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Activity;
use App\Models\Tenant\Client;
use App\Models\Tenant\Lead;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $query = Activity::with(['user', 'subject'])
            ->orderByDesc('occurred_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $activities = $query->paginate(30);

        return Inertia::render('Tenant/Manager/CRM/Activities', [
            'activities' => $activities,
            'filters' => $request->only(['type', 'user_id']),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'subject_type' => 'required|in:lead,client',
            'subject_id' => 'required|integer',
            'type' => 'required|in:call,email,meeting,note,demo,follow_up',
            'description' => 'nullable|string',
            'occurred_at' => 'nullable|date',
        ]);

        $subjectClass = $request->subject_type === 'lead' ? Lead::class : Client::class;
        $subject = $subjectClass::findOrFail($request->subject_id);

        ActivityService::log($subject, $request->type, $request->description, $user->id, $request->input('occurred_at', now()));

        return back()->with('success', __('messages.activity_saved'));
    }

    public function destroy(Activity $activity)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $activity->delete();

        return back()->with('success', __('messages.activity_deleted'));
    }
}
