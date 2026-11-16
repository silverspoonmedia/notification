<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\ActivityNotificationEvent;
use App\Domain\Notification\Services\NotificationService;

/**
 * Persists + fans out the role-targeted notification described by the event.
 *
 * Kept thin: all storage/visibility logic lives in NotificationService. Runs
 * synchronously (no queue) so the notification exists the moment the action
 * returns; switch to ShouldQueue here if it ever gets heavy.
 */
class SendActivityNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(ActivityNotificationEvent $event): void
    {
        $this->notifications->emit(
            $event->type,
            $event->data,
            $event->roles,
            $event->users,
            $event->actionRoute,
            $event->actionParams,
        );
    }
}
