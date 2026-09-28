<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Ticket;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A reply on a support ticket.
 *
 * An internal note is kept off the client portal: it is the place agents
 * write things the client is not meant to read, so it has to be impossible
 * to leak by accident.
 */
class TicketMessageController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'body' => 'required|string|max:10000',
            'is_internal' => 'boolean',
        ]);

        $message = $ticket->messages()->create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        // Answering reopens a ticket somebody had closed, and the first
        // reply is the one the SLA measures.
        $ticket->forceFill([
            'status' => $ticket->status === 'closed' ? 'open' : $ticket->status,
            'first_response_at' => $ticket->first_response_at ?? now(),
        ])->save();

        AuditService::log('ticket_replied', $ticket, [], ['message_id' => $message->id, 'internal' => $message->is_internal]);

        return back()->with('success', __('messages.reply_created'));
    }
}
