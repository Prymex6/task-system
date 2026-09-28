<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Contract;
use App\Models\Tenant\Estimate;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\KbArticle;
use App\Models\Tenant\KbCategory;
use App\Models\Tenant\Notification;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectFile;
use App\Models\Tenant\Proposal;
use App\Models\Tenant\Task;
use App\Models\Tenant\Ticket;
use App\Services\ProposalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PortalController extends Controller
{
    private function contact()
    {
        return Auth::guard('customer')->user();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $contact = $this->contact();
        $client = $contact->client;

        $activeProjects = Project::where('client_id', $client->id)
            ->where('status', 'in_progress')
            ->count();

        $openTickets = Ticket::where('client_id', $client->id)
            ->whereIn('status', ['open', 'pending'])
            ->count();

        $unpaidInvoices = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->selectRaw('SUM(total - paid_amount) as balance')
            ->value('balance') ?? 0;

        $recentProjects = Project::where('client_id', $client->id)
            ->where('is_archived', false)
            ->with('members')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'due_date']);

        $recentInvoices = Invoice::where('client_id', $client->id)
            ->latest('issue_date')
            ->limit(5)
            ->get(['id', 'number', 'total', 'status', 'due_date']);

        return Inertia::render('Tenant/Client/Dashboard', [
            'stats' => [
                'active_projects' => $activeProjects,
                'open_tickets' => $openTickets,
                'unpaid_invoices' => (float) $unpaidInvoices,
            ],
            'recentProjects' => $recentProjects,
            'recentInvoices' => $recentInvoices,
            'client' => $client,
        ]);
    }

    // ── Projekty ──────────────────────────────────────────────────────────────

    public function projects()
    {
        $contact = $this->contact();
        $projects = Project::where('client_id', $contact->client_id)
            ->where('is_archived', false)
            ->withCount('tasks')
            ->latest()
            ->paginate(10);

        return Inertia::render('Tenant/Client/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function projectShow(Project $project)
    {
        $contact = $this->contact();
        abort_unless($project->client_id === $contact->client_id, 403);

        $project->load(['tasks.assignees', 'tasks.status', 'milestones', 'members']);

        return Inertia::render('Tenant/Client/Projects/Show', [
            'project' => $project,
            'progress' => $project->progress,
        ]);
    }

    // ── Zadania ───────────────────────────────────────────────────────────────

    public function tasks(Request $request)
    {
        $contact = $this->contact();
        $query = Task::with(['project', 'status', 'assignees'])
            ->whereHas('project', fn ($q) => $q->where('client_id', $contact->client_id));

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->orderByRaw('ISNULL(due_date), due_date ASC')->paginate(20);

        return Inertia::render('Tenant/Client/Tasks/Index', [
            'tasks' => $tasks,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['project_id', 'status']),
        ]);
    }

    public function taskShow(Task $task)
    {
        $contact = $this->contact();
        abort_unless($task->project?->client_id === $contact->client_id, 403);

        $task->load(['project', 'status', 'assignees', 'checklists.items', 'comments' => fn ($q) => $q->where('is_internal', false)->with('creator')]);

        return Inertia::render('Tenant/Client/Tasks/Show', [
            'task' => $task,
        ]);
    }

    public function taskComment(Request $request, Task $task)
    {
        $contact = $this->contact();
        abort_unless($task->project?->client_id === $contact->client_id, 403);

        $request->validate(['body' => 'required|string|max:5000']);

        $task->comments()->create([
            'body' => $request->body,
            'created_by' => null,
            'is_internal' => false,
        ]);

        return back()->with('success', __('messages.comment_created'));
    }

    // ── Faktury ───────────────────────────────────────────────────────────────

    public function invoices(Request $request)
    {
        $contact = $this->contact();
        $invoices = Invoice::where('client_id', $contact->client_id)
            ->latest('issue_date')
            ->paginate(20);

        return Inertia::render('Tenant/Client/Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function invoiceShow(Invoice $invoice)
    {
        $contact = $this->contact();
        abort_unless($invoice->client_id === $contact->client_id, 403);

        $invoice->load(['items', 'payments']);

        return Inertia::render('Tenant/Client/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function invoicePdf(Invoice $invoice)
    {
        $contact = $this->contact();
        abort_unless($invoice->client_id === $contact->client_id, 403);

        $invoice->load(['client', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4');

        return $pdf->download('faktura-' . $invoice->number . '.pdf');
    }

    // ── Wyceny ────────────────────────────────────────────────────────────────

    public function estimates()
    {
        $contact = $this->contact();
        $estimates = Estimate::where('client_id', $contact->client_id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Estimates/Index', [
            'estimates' => $estimates,
        ]);
    }

    public function estimateAccept(Estimate $estimate)
    {
        $contact = $this->contact();
        abort_unless($estimate->client_id === $contact->client_id, 403);
        abort_unless($estimate->status === 'sent', 422);

        $estimate->update(['status' => 'accepted', 'accepted_at' => now(), 'accepted_by' => $contact->id]);

        return back()->with('success', __('messages.estimate_accepted'));
    }

    public function estimateReject(Estimate $estimate)
    {
        $contact = $this->contact();
        abort_unless($estimate->client_id === $contact->client_id, 403);
        abort_unless($estimate->status === 'sent', 422);

        $estimate->update(['status' => 'rejected']);

        return back()->with('success', __('messages.estimate_rejected'));
    }

    // ── Umowy ─────────────────────────────────────────────────────────────────

    public function contracts()
    {
        $contact = $this->contact();
        $contracts = Contract::where('client_id', $contact->client_id)
            ->whereIn('status', ['active', 'expired'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Contracts/Index', [
            'contracts' => $contracts,
        ]);
    }

    public function contractSign(Contract $contract)
    {
        $contact = $this->contact();
        abort_unless($contract->client_id === $contact->client_id, 403);

        $contract->update([
            'signed_by_client_at' => now(),
            'signed_by_client_id' => $contact->id,
        ]);

        return back()->with('success', __('messages.contract_signed'));
    }

    // ── Propozycje ────────────────────────────────────────────────────────────

    public function proposals()
    {
        $contact = $this->contact();
        $proposals = Proposal::where('client_id', $contact->client_id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Proposals/Index', [
            'proposals' => $proposals,
        ]);
    }

    public function proposalShow(Proposal $proposal)
    {
        $contact = $this->contact();
        abort_unless($proposal->client_id === $contact->client_id, 403);

        $proposal->markViewed();
        $proposal->load(['items', 'creator', 'signature']);

        return Inertia::render('Tenant/Client/Proposals/Show', [
            'proposal' => $proposal,
        ]);
    }

    public function proposalAccept(Request $request, Proposal $proposal)
    {
        $contact = $this->contact();
        abort_unless($proposal->client_id === $contact->client_id, 403);
        abort_unless(in_array($proposal->status, ['sent', 'viewed']), 422);

        $proposal->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return back()->with('success', __('messages.offer_accepted'));
    }

    public function proposalReject(Request $request, Proposal $proposal)
    {
        $contact = $this->contact();
        abort_unless($proposal->client_id === $contact->client_id, 403);
        abort_unless(in_array($proposal->status, ['sent', 'viewed']), 422);

        $proposal->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return back()->with('success', __('messages.offer_rejected'));
    }

    public function proposalPdf(Proposal $proposal)
    {
        $contact = $this->contact();
        abort_unless($proposal->client_id === $contact->client_id, 403);

        $proposal->load(['items', 'client', 'creator']);
        $pdf = ProposalService::generatePdf($proposal);

        return $pdf->download("oferta-{$proposal->number}.pdf");
    }

    // ── Wsparcie ──────────────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $contact = $this->contact();
        $tickets = Ticket::where('client_id', $contact->client_id)
            ->with(['messages'])
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function ticketCreate()
    {
        return Inertia::render('Tenant/Client/Tickets/Create');
    }

    public function ticketStore(Request $request)
    {
        $contact = $this->contact();
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::create([
            'client_id' => $contact->client_id,
            'client_contact_id' => $contact->id,
            'subject' => $validated['subject'],
            'status' => 'open',
            'priority' => $validated['priority'],
        ]);

        $ticket->messages()->create([
            'body' => $validated['message'],
            'client_contact_id' => $contact->id,
        ]);

        return redirect()->route('tenant.portal.tickets')
            ->with('success', __('messages.ticket_created'));
    }

    public function ticketShow(Ticket $ticket)
    {
        $contact = $this->contact();
        abort_unless($ticket->client_id === $contact->client_id, 403);

        $ticket->load('messages.sender');

        return Inertia::render('Tenant/Client/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function ticketReply(Request $request, Ticket $ticket)
    {
        $contact = $this->contact();
        abort_unless($ticket->client_id === $contact->client_id, 403);

        $validated = $request->validate(['message' => 'required|string|max:5000']);

        $ticket->messages()->create([
            'body' => $validated['message'],
            'client_contact_id' => $contact->id,
        ]);

        if (in_array($ticket->status, ['pending', 'on_hold'])) {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', __('messages.reply_sent'));
    }

    // ── Baza Wiedzy ───────────────────────────────────────────────────────────

    public function kb(Request $request)
    {
        $categories = KbCategory::withCount('articles')
            ->orderBy('name')
            ->get();

        $query = KbArticle::where('is_published', true)
            ->whereIn('visibility', ['public', 'clients_only']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $articles = $query->with('category')->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Client/KB/Index', [
            'categories' => $categories,
            'articles' => $articles,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function kbShow(KbArticle $article)
    {
        abort_unless($article->is_published && in_array($article->visibility, ['public', 'clients_only']), 404);

        $article->increment('views_count');
        $article->load('category');

        return Inertia::render('Tenant/Client/KB/Show', [
            'article' => $article,
        ]);
    }

    // ── Powiadomienia ─────────────────────────────────────────────────────────

    public function notifications()
    {
        $contact = $this->contact();
        $notifications = Notification::where('notifiable_id', $contact->id)
            ->where('notifiable_type', 'client_contact')
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Notifications', [
            'notifications' => $notifications,
        ]);
    }

    // ── Konto ─────────────────────────────────────────────────────────────────

    public function account()
    {
        $contact = $this->contact();
        $contact->load('client');

        return Inertia::render('Tenant/Client/Account', [
            'contact' => $contact,
        ]);
    }

    public function updateAccount(Request $request)
    {
        $contact = $this->contact();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
        ]);

        $contact->update($validated);

        return back()->with('success', __('messages.details_updated'));
    }

    public function changePassword(Request $request)
    {
        $contact = $this->contact();
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $contact->password)) {
            return back()->withErrors(['current_password' => __('messages.password_invalid')]);
        }

        $contact->update(['password' => Hash::make($request->password)]);

        return back()->with('success', __('messages.password_changed'));
    }

    // ── Pliki ─────────────────────────────────────────────────────────────────

    public function files()
    {
        $contact = $this->contact();

        $files = ProjectFile::whereHas('project', fn ($q) => $q->where('client_id', $contact->client_id))
            ->with(['project', 'uploader'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Files', [
            'files' => $files,
        ]);
    }
}
