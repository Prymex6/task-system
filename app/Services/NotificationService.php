<?php

namespace App\Services;

use App\Models\Tenant\Notification;
use App\Models\Tenant\User;

class NotificationService
{
    public static function send(User $user, string $type, string $title, string $message, ?string $link = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data,
            'read_at' => null,
        ]);
    }

    public static function markRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public static function markAllRead(User $user): void
    {
        Notification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public static function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    }

    public static function sendToMany(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                static::send($user, $type, $title, $message, $link);
            }
        }
    }

    public static function taskAssigned(User $user, $task): void
    {
        static::send(
            $user,
            'task_assigned',
            'Przypisano Ci zadanie',
            "Zostałeś przypisany do: {$task->title}",
            route('tenant.manager.tasks.show', $task)
        );
    }

    public static function taskCommentAdded(User $user, $task, $commenter): void
    {
        static::send(
            $user,
            'task_comment',
            'Nowy komentarz',
            "{$commenter->name} skomentował: {$task->title}",
            route('tenant.manager.tasks.show', $task)
        );
    }
}
