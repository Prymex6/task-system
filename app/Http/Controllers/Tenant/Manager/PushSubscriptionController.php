<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The browser handing over where to send its notifications.
 *
 * Keyed on the endpoint, because the same person on the same browser
 * re-registers on every visit and would otherwise collect a row each time.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'endpoint' => 'required|url|max:2048',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'staff_user_id' => $user->id,
                'p256dh_key' => $validated['keys']['p256dh'],
                'auth_key' => $validated['keys']['auth'],
            ]
        );

        return response()->json(['subscribed' => true]);
    }
}
