<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Lead;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * What was said to a lead and when — the call, the email, the meeting.
 */
class LeadActivityController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'type' => 'required|in:call,email,meeting,note',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'occurred_at' => 'nullable|date',
        ]);

        $activity = $lead->activities()->create([
            ...$validated,
            'occurred_at' => $validated['occurred_at'] ?? now(),
            'user_id' => $user->id,
        ]);

        AuditService::log('lead_activity_logged', $lead, [], ['type' => $activity->type, 'title' => $activity->title]);

        return back()->with('success', __('messages.activity_saved'));
    }
}
