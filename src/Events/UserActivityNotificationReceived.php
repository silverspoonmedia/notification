<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Notifications\NotificationType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Realtime state update on a user's private channel when a new activity
 * notification is visible to them. Carries the recipient's absolute unread count
 * so the bell updates without HTTP; recent-list copy remains HTTP/localized. Also
 * carries type + tableId for DataTable live reload.
 */
class UserActivityNotificationReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly NotificationType $type,
        public readonly int $unreadCount,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity-notification.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type->value,
            'table_id' => $this->type->tableId(),
            'unread_count' => $this->unreadCount,
        ];
    }
}
