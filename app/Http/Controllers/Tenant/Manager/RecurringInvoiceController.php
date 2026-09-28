<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\RecurringInvoice;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\User;
use App\Services\AuditService;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Standing orders for invoices: bill this client, these lines, every month.
 *
 * The lines live in `template_data` rather than their own table, because a
 * schedule is a recipe — once an invoice is issued it keeps its own copy.
 */
class RecurringInvoiceController extends Controller
{
    public function index()
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Finance/RecurringInvoices/Index', [
            'recurring' => RecurringInvoice::with('client:id,name')
                ->orderByDesc('is_active')
                ->orderBy('next_date')
                ->paginate(25),
        ]);
    }

    public function create()
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Finance/RecurringInvoices/Form', $this->formData());
    }

    public function store(Request $request)
    {
        $user = $this->authorizeManager();

        $validated = $request->validate($this->rules());

        $recurring = RecurringInvoice::create([
            ...collect($validated)->except('template_data')->all(),
            'created_by' => $user->id,
            'template_data' => $validated['template_data'],
        ]);

        AuditService::log('recurring_invoice_created', $recurring, [], $recurring->only(['title', 'frequency']));

        return redirect()->route('tenant.manager.recurring-invoices.index')
            ->with('success', __('messages.recurring_invoice_created'));
    }

    public function show(RecurringInvoice $recurringInvoice)
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Finance/RecurringInvoices/Show', [
            'recurring' => $recurringInvoice->load('client:id,name', 'creator:id,name'),
        ]);
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Finance/RecurringInvoices/Form', [
            ...$this->formData(),
            'recurring' => $recurringInvoice,
        ]);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        $this->authorizeManager();

        $old = $recurringInvoice->only(['title', 'frequency', 'is_active']);
        $recurringInvoice->update($request->validate($this->rules()));

        AuditService::log('recurring_invoice_updated', $recurringInvoice, $old, $recurringInvoice->only(['title', 'frequency', 'is_active']));

        return back()->with('success', __('messages.recurring_invoice_updated'));
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $this->authorizeManager();

        $recurringInvoice->delete();

        return redirect()->route('tenant.manager.recurring-invoices.index')
            ->with('success', __('messages.recurring_invoice_deleted'));
    }

    /**
     * Issue one now without waiting for its date, for the case where a client
     * asks for the next invoice early.
     */
    public function generate(RecurringInvoice $recurringInvoice)
    {
        $this->authorizeManager();

        $invoice = RecurringInvoiceService::generateInvoice($recurringInvoice);

        return redirect()->route('tenant.manager.invoices.show', $invoice)
            ->with('success', __('messages.invoice_generated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::orderBy('rate')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'client_id' => 'nullable|integer|exists:clients,id',
            'title' => 'required|string|max:255',
            'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'interval' => 'required|integer|min:1|max:12',
            'next_date' => 'required|date',
            'ends_at' => 'nullable|date|after:next_date',
            'is_active' => 'boolean',
            'template_data' => 'required|array',
            'template_data.items' => 'required|array|min:1',
            'template_data.items.*.description' => 'required|string|max:255',
            'template_data.items.*.quantity' => 'required|numeric|min:0.01',
            'template_data.items.*.unit_price' => 'required|numeric|min:0',
            'template_data.items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'template_data.currency' => 'nullable|string|size:3',
            'template_data.payment_days' => 'nullable|integer|min:0|max:365',
            'template_data.notes' => 'nullable|string|max:2000',
        ];
    }

    private function authorizeManager(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        return $user;
    }
}
