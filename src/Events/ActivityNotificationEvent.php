<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Notifications\NotificationType;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever an action should raise an activity notification.
 *
 * This is the single generic entry point: any feature (user created, order
 * placed, export ready, …) dispatches this event and the SendActivityNotification
 * listener persists + fans it out. Emitters only describe WHAT happened and WHO
 * should see it — by role (broadcast), by user (personal), or both.
 */
class ActivityNotificationEvent
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data  Message replacements + render context.
     * @param  list<string>|null  $roles  Target role names; null = type defaults.
     * @param  list<string>  $users  Target user ids (personal audience).
     * @param  array<string, mixed>  $actionParams  Route params for the deep link.
     */
    public function __construct(
        public readonly NotificationType $type,
        public readonly array $data = [],
        public readonly ?array $roles = null,
        public readonly array $users = [],
        public readonly ?string $actionRoute = null,
        public readonly array $actionParams = [],
    ) {}
}
