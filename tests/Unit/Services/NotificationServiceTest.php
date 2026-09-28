<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Notification;
use App\Models\Tenant\Task;
use App\Models\Tenant\User;
use App\Services\NotificationService;
use Tests\TenantTestCase;

class NotificationServiceTest extends TenantTestCase
{
    private function makeUser(): User
    {
        return User::factory()->create(['workspace_role' => 'member']);
    }

    public function test_send_creates_notification_for_user(): void
    {
        $user = $this->makeUser();

        $notification = NotificationService::send(
            $user,
            'test_event',
            'Tytuł testowy',
            'Wiadomość testowa',
            '/tasks/1'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'test_event',
            'title' => 'Tytuł testowy',
        ]);
        $this->assertNull($notification->read_at);
    }

    public function test_mark_read_sets_read_at_timestamp(): void
    {
        $user = $this->makeUser();
        $notification = NotificationService::send($user, 'test', 'Tytuł', 'Treść');

        NotificationService::markRead($notification);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_clears_all_unread_for_user(): void
    {
        $user = $this->makeUser();
        NotificationService::send($user, 'a', 'Pierwsza', 'Treść 1');
        NotificationService::send($user, 'b', 'Druga', 'Treść 2');
        NotificationService::send($user, 'c', 'Trzecia', 'Treść 3');

        NotificationService::markAllRead($user);

        $count = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
        $this->assertEquals(0, $count);
    }

    public function test_mark_all_read_does_not_affect_other_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        NotificationService::send($user1, 'a', 'User1', 'Treść');
        NotificationService::send($user2, 'a', 'User2', 'Treść');

        NotificationService::markAllRead($user1);

        $unreadUser2 = NotificationService::getUnreadCount($user2);
        $this->assertEquals(1, $unreadUser2);
    }

    public function test_get_unread_count_returns_correct_count(): void
    {
        $user = $this->makeUser();
        NotificationService::send($user, 'a', 'Pierwsza', 'Treść 1');
        NotificationService::send($user, 'b', 'Druga', 'Treść 2');
        $notification = NotificationService::send($user, 'c', 'Trzecia', 'Treść 3');
        NotificationService::markRead($notification);

        $count = NotificationService::getUnreadCount($user);

        $this->assertEquals(2, $count);
    }

    public function test_send_to_many_creates_notifications_for_all_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        NotificationService::sendToMany(
            [$user1->id, $user2->id],
            'broadcast',
            'Ogłoszenie',
            'Treść ogłoszenia'
        );

        $this->assertDatabaseHas('notifications', ['user_id' => $user1->id, 'type' => 'broadcast']);
        $this->assertDatabaseHas('notifications', ['user_id' => $user2->id, 'type' => 'broadcast']);
    }

    public function test_task_assigned_creates_correct_notification(): void
    {
        $user = $this->makeUser();
        $task = Task::factory()->create(['title' => 'Ważne zadanie']);

        NotificationService::taskAssigned($user, $task);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'task_assigned',
        ]);
    }

    public function test_task_comment_added_creates_notification(): void
    {
        $user = $this->makeUser();
        $commenter = $this->makeUser();
        $task = Task::factory()->create(['title' => 'Skomentowane zadanie']);

        NotificationService::taskCommentAdded($user, $task, $commenter);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'task_comment',
        ]);
    }
}
