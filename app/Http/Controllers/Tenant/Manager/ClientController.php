<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use App\Models\Tenant\ClientGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount(['projects', 'invoices']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('company_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('client_groups.id', $request->group_id));
        }

        $clients = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/CRM/Clients/Index', [
            'clients' => $clients,
            'groups' => ClientGroup::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'group_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/CRM/Clients/Form', [
            'groups' => ClientGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:20',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:5',
            'notes' => 'nullable|string|max:2000',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:client_groups,id',
            // Portal contact
            'create_portal_access' => 'boolean',
            'contact_name' => 'required_if:create_portal_access,true|nullable|string|max:255',
            'contact_email' => 'required_if:create_portal_access,true|nullable|email|unique:client_contacts,email',
            'contact_password' => 'required_if:create_portal_access,true|nullable|string|min:8',
        ]);

        $client = Client::create(collect($validated)->except(['groups', 'create_portal_access', 'contact_name', 'contact_email', 'contact_password'])->toArray());

        if (!empty($validated['groups'])) {
            $client->groups()->sync($validated['groups']);
        }

        if ($request->boolean('create_portal_access') && !empty($validated['contact_email'])) {
            ClientContact::create([
                'client_id' => $client->id,
                'name' => $validated['contact_name'],
                'email' => $validated['contact_email'],
                'password' => Hash::make($validated['contact_password']),
                'is_primary' => true,
                'portal_access' => true,
            ]);
        }

        Log::info('CRM: dodano klienta', ['client_id' => $client->id, 'by' => Auth::guard('tenant')->id()]);

        return redirect()->route('tenant.manager.clients.show', $client)
            ->with('success', __('messages.client_created'));
    }

    public function show(Client $client)
    {
        $client->load(['contacts', 'groups', 'projects', 'invoices', 'estimates', 'tickets']);

        return Inertia::render('Tenant/Manager/CRM/Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client)
    {
        return Inertia::render('Tenant/Manager/CRM/Clients/Form', [
            'client' => $client->load('groups'),
            'groups' => ClientGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:20',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:5',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:client_groups,id',
        ]);

        $client->update(collect($validated)->except('groups')->toArray());

        if (array_key_exists('groups', $validated)) {
            $client->groups()->sync($validated['groups'] ?? []);
        }

        return back()->with('success', __('messages.client_updated'));
    }

    public function destroy(Client $client)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        Log::info('CRM: usunięto klienta', ['client_id' => $client->id, 'by' => $user->id]);
        $client->delete();

        return redirect()->route('tenant.manager.clients.index')
            ->with('success', __('messages.client_deleted'));
    }

    // ── Kontakty portalu ──────────────────────────────────────────────────────

    public function addContact(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_contacts,email',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'portal_access' => 'boolean',
            'password' => 'required_if:portal_access,true|nullable|string|min:8',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $client->contacts()->create(array_merge($validated, ['client_id' => $client->id]));

        return back()->with('success', __('messages.contact_created'));
    }

    public function removeContact(Client $client, ClientContact $contact)
    {
        abort_unless($contact->client_id === $client->id, 404);
        $contact->delete();

        return back()->with('success', __('messages.contact_deleted'));
    }
}
