<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\SupportTicket;
use App\Models\Landlord\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Support the platform gives its tenants, as opposed to the helpdesk a tenant
 * gives its own clients.
 *
 * Opening a ticket clears its unread flag, so the list is a queue of what has
 * not been looked at rather than of everything ever sent.
 */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::with('latestMessage')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('tenant_id'), fn ($q, $id) => $q->where('tenant_id', $id))
            ->orderByDesc('unread_by_admin')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Landlord/Support/Index', [
            'tickets' => $tickets,
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'tenant_id']),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load('messages');
        $ticket->update(['unread_by_admin' => false]);

        return Inertia::render('Landlord/Support/Show', [
            'ticket' => $ticket,
            'tenantName' => Tenant::where('id', $ticket->tenant_id)->value('name'),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate(['message' => 'required|string|max:5000']);

        $ticket->messages()->create([
            'author_type' => 'admin',
            'author_name' => auth('super_admin')->user()?->name ?? 'Support',
            'message' => $validated['message'],
        ]);

        $ticket->update(['status' => 'answered', 'unread_by_admin' => false]);

        return back()->with('success', __('messages.reply_sent'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,answered,closed',
        ]);

        $ticket->update($validated);

        return back()->with('success', __('messages.ticket_updated'));
    }
}
