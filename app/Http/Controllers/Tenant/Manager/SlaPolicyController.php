<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SlaPolicy;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * How quickly a ticket of a given priority has to be answered and closed.
 */
class SlaPolicyController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/SlaPolicies', [
            'policies' => SlaPolicy::orderBy('priority')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = SlaPolicy::create($validated);

        AuditService::log('sla_policy_created', $record, [], $record->only(['name', 'priority', 'response_hours', 'resolution_hours']));

        return back()->with('success', __('messages.sla_policy_created'));
    }

    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $old = $slaPolicy->only(['name', 'priority', 'response_hours', 'resolution_hours']);
        $slaPolicy->update($validated);

        AuditService::log('sla_policy_updated', $slaPolicy, $old, $slaPolicy->fresh()->only(['name', 'priority', 'response_hours', 'resolution_hours']));

        return back()->with('success', __('messages.sla_policy_updated'));
    }

    public function destroy(SlaPolicy $slaPolicy)
    {
        $this->admin();

        $slaPolicy->delete();

        AuditService::log('sla_policy_deleted', $slaPolicy, ['name' => $slaPolicy->name], []);

        return back()->with('success', __('messages.sla_policy_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
            'response_hours' => 'required|integer|min:1|max:8760',
            'resolution_hours' => 'required|integer|min:1|max:8760',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
