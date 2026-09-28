<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Webhook;
use App\Services\AuditService;
use App\Services\WebhookDispatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebhookController extends Controller
{
    /**
     * Events a webhook can subscribe to.
     *
     * The same list the create form offers. Anything outside it is refused,
     * so a typo does not become a subscription that never fires.
     */
    public const EVENTS = [
        'task.created',
        'task.status_changed',
        'invoice.paid',
        'ticket.created',
        'ticket.closed',
        'deal.stage_changed',
        'project.completed',
        'lead.converted',
    ];

    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/Webhooks', [
            'webhooks' => Webhook::orderByDesc('id')->get(),
            'availableEvents' => self::EVENTS,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', self::EVENTS),
            'name' => 'nullable|string|max:100',
        ]);

        $webhook = Webhook::create([
            ...$validated,
            'name' => $validated['name'] ?? parse_url($validated['url'], PHP_URL_HOST),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        AuditService::log('webhook_created', $webhook, [], $webhook->only(['url', 'events']));

        return back()->with('success', __('messages.webhook_created'));
    }

    public function update(Request $request, Webhook $webhook)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $request->validate([
            'url' => 'sometimes|required|url|max:2048',
            'secret' => 'nullable|string|max:255',
            'events' => 'sometimes|required|array|min:1',
            'events.*' => 'string|in:' . implode(',', self::EVENTS),
            'is_active' => 'sometimes|boolean',
        ]);

        $old = $webhook->only(['url', 'events', 'is_active']);

        // Switching a webhook back on clears the failures that switched it
        // off, otherwise the next one would disable it again immediately.
        if (($validated['is_active'] ?? false) && !$webhook->is_active) {
            $validated['failure_count'] = 0;
        }

        $webhook->update($validated);

        AuditService::log('webhook_updated', $webhook, $old, $webhook->fresh()->only(['url', 'events', 'is_active']));

        return back()->with('success', __('messages.webhook_updated'));
    }

    public function destroy(Webhook $webhook)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $webhook->delete();

        AuditService::log('webhook_deleted', $webhook, ['url' => $webhook->url], []);

        return back()->with('success', __('messages.webhook_deleted'));
    }

    /**
     * Deliver one message so somebody can see whether the far end accepts it.
     */
    public function test(Webhook $webhook, WebhookDispatcherService $dispatcher): JsonResponse
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $log = $dispatcher->deliver($webhook, 'webhook.test', [
            'message' => 'Test delivery from ' . config('app.name'),
            'sent_by' => $user->name,
        ]);

        return response()->json([
            'success' => $log->success,
            'status' => $log->response_status,
        ]);
    }
}
