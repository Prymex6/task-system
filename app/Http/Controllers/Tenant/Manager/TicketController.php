<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CannedResponse;
use App\Models\Tenant\Department;
use App\Models\Tenant\Ticket;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['client', 'contact', 'assignee', 'department'])
            ->withCount('messages');

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('assignee_id')) {
            $query->where('assigned_to', $request->assignee_id);
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Support/Index', [
            'tickets' => $tickets,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'priority', 'department_id', 'assignee_id']),
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'client',
            'contact',
            'assignee',
            'department',
            'messages.user',
            'messages.contact',
            'attachments',
            'rating',
        ]);

        $cannedResponses = CannedResponse::orderBy('title')->get(['id', 'title', 'body']);
        $staff = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/Support/Show', [
            'ticket' => $ticket,
            'cannedResponses' => $cannedResponses,
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,pending,on_hold,resolved,closed',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $ticket->update($validated);

        return back()->with('success', __('messages.ticket_updated'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:20480',
        ]);

        $message = $ticket->messages()->create([
            'body' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
            'user_id' => $user->id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("tickets/{$ticket->id}", 'public');
                $message->attachments()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        // Jeśli nie jest wewnętrzny, zmień status na in_progress
        if (!$validated['is_internal'] && $ticket->status === 'open') {
            $ticket->update(['status' => 'pending']);
        }

        return back()->with('success', __('messages.reply_sent'));
    }

    public function destroy(Ticket $ticket)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        $ticket->delete();

        return redirect()->route('tenant.manager.tickets.index')
            ->with('success', __('messages.ticket_deleted'));
    }

    public function close(Ticket $ticket)
    {
        $ticket->update(['status' => 'closed']);

        return back()->with('success', __('messages.ticket_closed'));
    }

    public function reopen(Ticket $ticket)
    {
        $ticket->update(['status' => 'open']);

        return back()->with('success', __('messages.ticket_reopened'));
    }
}
