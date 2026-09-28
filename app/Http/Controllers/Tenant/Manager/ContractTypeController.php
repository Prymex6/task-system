<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ContractType;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * The kinds of contract a client agreement can be filed under.
 */
class ContractTypeController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/ContractTypes', [
            'types' => ContractType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = ContractType::create($validated);

        AuditService::log('contract_type_created', $record, [], $record->only(['name']));

        return back()->with('success', __('messages.contract_type_created'));
    }

    public function update(Request $request, ContractType $type)
    {
        $this->admin();

        $validated = $request->validate($this->rules($type->id));

        $old = $type->only(['name']);
        $type->update($validated);

        AuditService::log('contract_type_updated', $type, $old, $type->fresh()->only(['name']));

        return back()->with('success', __('messages.contract_type_updated'));
    }

    public function destroy(ContractType $type)
    {
        $this->admin();

        $type->delete();

        AuditService::log('contract_type_deleted', $type, ['name' => $type->name], []);

        return back()->with('success', __('messages.contract_type_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'name' => 'required|string|max:100|unique:contract_types,name,' . $ignore,
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
