<?php

namespace App\Domain\Notification\Tests\Feature\Http\Controllers\API\V1;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Services\User\UserManagementService;
use App\Domain\Notification\Database\Seeders\NotificationTypeSettingSeeder;
use App\Domain\Notification\Events\UserActivityNotificationReceived;
use App\Domain\Notification\Models\ActivityNotification;
use App\Domain\Notification\Notifications\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Tests\TestCase;
use App\Support\Testing\Concerns\SeedsRolesAndPermissions;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Event;

class NotificationTest extends TestCase
{
    use SeedsRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        // The kernel trait seeds the ladder only (it may not name a sibling
        // package); the per-type audience defaults are this package's own.
        $this->seed(NotificationTypeSettingSeeder::class);
    }

    public function test_creating_a_user_fans_out_a_notification_to_root_and_admin(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAsJwt($admin)->postJson('/api/v1/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Moderator',
        ])->assertCreated();

        $this->assertDatabaseCount('activity_notifications', 1);

        $notification = ActivityNotification::query()->firstOrFail();
        $this->assertSame(NotificationType::UserCreated->value, $notification->type);
        $this->assertSame('jane@example.com', $notification->data['email']);

        $targetRoles = $notification->roles->pluck('name')->sort()->values()->all();
        $this->assertSame(['Admin', 'Root'], $targetRoles);
    }

    public function test_moderator_can_access_surface_but_does_not_see_root_admin_broadcast(): void
    {
        $admin = $this->userWithRole('Admin');
        $moderator = $this->userWithRole('Moderator');

        $this->emitUserCreated();

        $this->actingAsJwt($admin)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Moderator now holds `view notification` (per-user notifications can reach
        // any role), but is not in the Root/Admin broadcast audience → empty list.
        $this->actingAsJwt($moderator)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_personal_notification_reaches_only_the_targeted_user(): void
    {
        $alice = $this->userWithRole('User');
        $bob = $this->userWithRole('User');

        app(NotificationService::class)->sendToUsers(
            NotificationType::AccountUpdated,
            [$alice->id],
            ['name' => $alice->name, 'actor' => 'Admin User'],
        );

        $this->actingAsJwt($alice)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', NotificationType::AccountUpdated->value);

        // Bob shares the same role but was not targeted → sees nothing
        // (per-user is not a role broadcast).
        $this->actingAsJwt($bob)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_updating_a_user_sends_a_personal_notification_to_that_user(): void
    {
        $admin = $this->userWithRole('Admin');
        $target = $this->userWithRole('Moderator');

        $this->actingAsJwt($admin)
            ->putJson("/api/v1/users/{$target->id}", ['name' => 'Renamed'])
            ->assertOk();

        $personal = ActivityNotification::query()
            ->where('type', NotificationType::AccountUpdated->value)
            ->firstOrFail();
        $this->assertTrue($personal->targetUsers->contains('id', $target->id));
        $this->assertCount(0, $personal->roles);

        // The edited user sees their personal notification.
        $this->actingAsJwt($target)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_updating_a_user_also_broadcasts_a_list_reload_to_root_and_admin(): void
    {
        $admin = $this->userWithRole('Admin');
        $target = $this->userWithRole('Moderator');

        $this->actingAsJwt($admin)
            ->putJson("/api/v1/users/{$target->id}", ['name' => 'Renamed'])
            ->assertOk();

        // Broadcast list-change signal: Root + Admin audience, users-table reload,
        // subject NOT a personal target.
        $broadcast = ActivityNotification::query()
            ->where('type', NotificationType::UserUpdated->value)
            ->firstOrFail();
        $roles = $broadcast->roles->pluck('name')->sort()->values()->all();
        $this->assertSame(['Admin', 'Root'], $roles);
        $this->assertFalse($broadcast->targetUsers->contains('id', $target->id));
        $this->assertSame('users-table', NotificationType::UserUpdated->tableId());
    }

    public function test_admin_editing_another_user_does_not_see_the_personal_account_updated(): void
    {
        // Regression for the mis-target bug: editing khaled must not surface the
        // personal "Your account was updated" note in the actor-admin's bell,
        // even if a stray role pivot polluted the type setting.
        $admin = $this->userWithRole('Admin');
        $target = $this->userWithRole('Moderator');

        $this->actingAsJwt($admin)
            ->putJson("/api/v1/users/{$target->id}", ['name' => 'Renamed'])
            ->assertOk();

        // Admin sees the broadcast list-reload note (user_updated) but never the
        // personal account_updated addressed to the target.
        $adminList = $this->actingAsJwt($admin)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->json('data');

        $types = array_column($adminList, 'type');
        $this->assertContains(NotificationType::UserUpdated->value, $types);
        $this->assertNotContains(NotificationType::AccountUpdated->value, $types);
    }

    public function test_deleting_a_user_broadcasts_a_list_reload_to_root_and_admin(): void
    {
        $admin = $this->userWithRole('Admin');
        $target = $this->userWithRole('User');

        $this->actingAsJwt($admin)
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertOk();

        $broadcast = ActivityNotification::query()
            ->where('type', NotificationType::UserDeleted->value)
            ->firstOrFail();
        $roles = $broadcast->roles->pluck('name')->sort()->values()->all();
        $this->assertSame(['Admin', 'Root'], $roles);
        $this->assertSame($target->email, $broadcast->data['email']);
        $this->assertSame('users-table', NotificationType::UserDeleted->tableId());
    }

    public function test_self_edit_does_not_create_a_personal_notification(): void
    {
        // Self-edit can't reach the HTTP endpoint (UserPolicy denies equal-level
        // updates), so assert the service guard directly: editing yourself emits
        // no AccountUpdated notification.
        $admin = $this->userWithRole('Admin');

        app(UserManagementService::class)
            ->update($admin, $admin->id, ['name' => 'Myself Renamed']);

        $this->assertDatabaseMissing('activity_notifications', [
            'type' => NotificationType::AccountUpdated->value,
        ]);
    }

    public function test_unread_count_and_mark_as_read(): void
    {
        $admin = $this->userWithRole('Admin');
        $notification = $this->emitUserCreated();

        $this->actingAsJwt($admin)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 1);

        $this->actingAsJwt($admin)
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->actingAsJwt($admin)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 0);
    }

    public function test_mark_all_as_read(): void
    {
        $admin = $this->userWithRole('Admin');
        $this->emitUserCreated();
        $this->emitUserCreated();

        $this->actingAsJwt($admin)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 2)
            ->assertJsonPath('data.unread', 0);

        $this->actingAsJwt($admin)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread', 0);
    }

    public function test_cannot_mark_notification_out_of_scope(): void
    {
        // A broadcast targeted only at Root, with no per-user target.
        $rootOnly = ActivityNotification::factory()->create();
        $rootOnly->roles()->sync(Role::query()->where('name', 'Root')->pluck('id'));

        $admin = $this->userWithRole('Admin');

        $this->actingAsJwt($admin)
            ->postJson("/api/v1/notifications/{$rootOnly->id}/read")
            ->assertForbidden();
    }

    public function test_emit_broadcasts_realtime_ping_when_delivery_is_realtime_and_pusher_is_configured(): void
    {
        Event::fake([UserActivityNotificationReceived::class]);

        $this->configurePusherBroadcasting();
        config(['notifications.delivery' => 'realtime']);

        $alice = $this->userWithRole('User');

        app(NotificationService::class)->sendToUsers(
            NotificationType::AccountUpdated,
            [$alice->id],
            ['name' => $alice->name, 'actor' => 'Admin User'],
        );

        Event::assertDispatched(UserActivityNotificationReceived::class, function (UserActivityNotificationReceived $event) use ($alice): bool {
            return $event->userId === $alice->id
                && $event->unreadCount === 1
                && $event->broadcastWith()['unread_count'] === 1;
        });
    }

    public function test_realtime_payload_carries_absolute_unread_count_for_recipient(): void
    {
        Event::fake([UserActivityNotificationReceived::class]);

        $this->configurePusherBroadcasting();
        config(['notifications.delivery' => 'realtime']);

        $alice = $this->userWithRole('User');

        // First notification creates unread count 1; second event must carry 2.
        app(NotificationService::class)->sendToUsers(
            NotificationType::AccountUpdated,
            [$alice->id],
            ['name' => $alice->name, 'actor' => 'Admin User'],
        );
        app(NotificationService::class)->sendToUsers(
            NotificationType::AccountUpdated,
            [$alice->id],
            ['name' => $alice->name, 'actor' => 'Admin User'],
        );

        Event::assertDispatched(UserActivityNotificationReceived::class, function (UserActivityNotificationReceived $event) use ($alice): bool {
            return $event->userId === $alice->id
                && $event->unreadCount === 2
                && $event->broadcastWith()['unread_count'] === 2;
        });
    }

    public function test_emit_does_not_broadcast_when_delivery_is_polling(): void
    {
        Event::fake([UserActivityNotificationReceived::class]);

        $this->configurePusherBroadcasting();
        config(['notifications.delivery' => 'polling']);

        $alice = $this->userWithRole('User');

        app(NotificationService::class)->sendToUsers(
            NotificationType::AccountUpdated,
            [$alice->id],
            ['name' => $alice->name, 'actor' => 'Admin User'],
        );

        Event::assertNotDispatched(UserActivityNotificationReceived::class);
    }

    private function emitUserCreated(): ActivityNotification
    {
        $notification = ActivityNotification::factory()->create();
        $notification->roles()->sync(
            Role::query()->whereIn('name', ['Root', 'Admin'])->pluck('id'),
        );

        return $notification;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function actingAsJwt(User $user): static
    {
        $token = auth('api')->login($user);

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    private function configurePusherBroadcasting(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher',
                'key' => 'key',
                'secret' => 'secret',
                'app_id' => 'app',
                'options' => [],
            ],
        ]);

        $this->app->forgetInstance(BroadcastManager::class);
    }
}
