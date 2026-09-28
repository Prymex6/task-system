<?php

namespace App\Services;

use App\Models\Tenant\Reminder;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    public static function create(User $user, string $type, string $message, string $remindAt, ?int $relatedId = null, ?string $relatedType = null): Reminder
    {
        return Reminder::create([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'remind_at' => $remindAt,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'is_sent' => false,
        ]);
    }

    public static function processDue(): void
    {
        Reminder::where('is_sent', false)
            ->where('remind_at', '<=', now())
            ->with('user')
            ->get()
            ->each(function (Reminder $reminder) {
                try {
                    NotificationService::send(
                        $reminder->user,
                        'reminder',
                        'Przypomnienie',
                        $reminder->message
                    );
                    $reminder->update(['is_sent' => true, 'sent_at' => now()]);
                } catch (\Throwable $e) {
                    Log::error('ReminderService: failed', ['id' => $reminder->id, 'error' => $e->getMessage()]);
                }
            });
    }
}
