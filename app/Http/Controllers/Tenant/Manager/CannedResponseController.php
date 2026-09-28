<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CannedResponse;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Ready-made replies an agent can drop into a ticket.
 */
class CannedResponseController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/CannedResponses', [
            'responses' => CannedResponse::orderBy('title')->get(),
        ]);
    }

    /**
     * Both are edited in a modal on the list, so there is no separate page.
     */
    public function create()
    {
        return redirect()->route('tenant.manager.support.canned-responses.index');
    }

    public function edit(CannedResponse $cannedResponse)
    {
        return redirect()->route('tenant.manager.support.canned-responses.index');
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = CannedResponse::create($validated);

        AuditService::log('canned_response_created', $record, [], $record->only(['title']));

        return back()->with('success', __('messages.canned_response_created'));
    }

    public function update(Request $request, CannedResponse $cannedResponse)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $old = $cannedResponse->only(['title']);
        $cannedResponse->update($validated);

        AuditService::log('canned_response_updated', $cannedResponse, $old, $cannedResponse->fresh()->only(['title']));

        return back()->with('success', __('messages.canned_response_updated'));
    }

    public function destroy(CannedResponse $cannedResponse)
    {
        $this->admin();

        $cannedResponse->delete();

        AuditService::log('canned_response_deleted', $cannedResponse, ['title' => $cannedResponse->title], []);

        return back()->with('success', __('messages.canned_response_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:5000',
            'department_id' => 'nullable|integer|exists:departments,id',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
