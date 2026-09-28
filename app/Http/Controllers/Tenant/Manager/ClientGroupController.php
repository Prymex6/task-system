<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ClientGroup;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Groups clients are sorted into, for filtering and reporting.
 */
class ClientGroupController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/ClientGroups', [
            'groups' => ClientGroup::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = ClientGroup::create($validated);

        AuditService::log('client_group_created', $record, [], $record->only(['name', 'color']));

        return back()->with('success', __('messages.client_group_created'));
    }

    public function update(Request $request, ClientGroup $clientGroup)
    {
        $this->admin();

        $validated = $request->validate($this->rules($clientGroup->id));

        $old = $clientGroup->only(['name', 'color']);
        $clientGroup->update($validated);

        AuditService::log('client_group_updated', $clientGroup, $old, $clientGroup->fresh()->only(['name', 'color']));

        return back()->with('success', __('messages.client_group_updated'));
    }

    public function destroy(ClientGroup $clientGroup)
    {
        $this->admin();

        $clientGroup->delete();

        AuditService::log('client_group_deleted', $clientGroup, ['name' => $clientGroup->name], []);

        return back()->with('success', __('messages.client_group_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'name' => 'required|string|max:100|unique:client_groups,name,' . $ignore,
            'color' => 'required|string|max:7',
            'description' => 'nullable|string|max:500',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
