<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientContactController extends Controller
{
    public function index(Client $client)
    {
        return Inertia::render('Tenant/Manager/Clients/Contacts', [
            'client' => $client,
            'contacts' => $client->contacts()->get(),
        ]);
    }

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_contacts,email',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
            'portal_access' => 'nullable|boolean',
        ]);

        $client->contacts()->create($validated);

        return back()->with('success', __('messages.contact_created'));
    }

    public function update(Request $request, Client $client, ClientContact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_contacts,email,' . $contact->id,
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
            'portal_access' => 'nullable|boolean',
        ]);

        $contact->update($validated);

        return back()->with('success', __('messages.contact_updated'));
    }

    public function destroy(Client $client, ClientContact $contact)
    {
        $contact->delete();

        return back()->with('success', __('messages.contact_deleted'));
    }
}
