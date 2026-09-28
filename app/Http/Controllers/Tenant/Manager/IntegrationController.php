<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Third-party connections, one row per service.
 *
 * Credentials live in the `config` blob and never travel back to the browser;
 * the settings screen only needs to know whether a service is wired up.
 */
class IntegrationController extends Controller
{
    /** Config keys accepted per service — anything else in the payload is dropped. */
    private const FIELDS = [
        'slack' => ['webhook_url' => 'required|url|max:500'],
        'github' => ['token' => 'required|string|max:255'],
        'google_calendar' => ['calendar_id' => 'required|string|max:255'],
        'zapier' => ['webhook_url' => 'nullable|url|max:500'],
    ];

    public function index()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/Integrations', [
            'integrations' => Integration::query()
                ->get()
                ->map(fn (Integration $i) => [
                    'type' => $i->type,
                    'is_active' => $i->is_active,
                    'connected_at' => $i->created_at,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $type = $request->input('type');
        abort_unless(is_string($type) && array_key_exists($type, self::FIELDS), 404);

        $rules = ['config' => 'present|array'];
        foreach (self::FIELDS[$type] as $field => $rule) {
            $rules['config.' . $field] = $rule;
        }

        $validated = $request->validate($rules);

        Integration::updateOrCreate(
            ['type' => $type],
            ['config' => $validated['config'] ?? [], 'is_active' => true],
        );

        return back()->with('success', __('messages.integration_connected'));
    }

    public function destroy(string $type)
    {
        $this->authorizeAdmin();

        abort_unless(array_key_exists($type, self::FIELDS), 404);

        Integration::where('type', $type)->delete();

        return back()->with('success', __('messages.integration_disconnected'));
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
