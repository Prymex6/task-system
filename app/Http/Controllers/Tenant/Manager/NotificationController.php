<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Notification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(30);

        return Inertia::render('Tenant/Manager/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function recent()
    {
        $user = Auth::guard('tenant')->user();
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'type', 'data', 'is_read', 'link', 'created_at']);

        $unread = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'is_read' => $n->is_read,
                'url' => $n->url,
                'time' => $n->created_at->diffForHumans(),
            ]),
            'unread' => $unread,
        ]);
    }

    public function markRead(Notification $notification)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($notification->user_id === $user->id, 403);

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $user = Auth::guard('tenant')->user();
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
