<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Notifications\NotificationType;
use App\Support\Notifications\Contracts\NotificationDispatcher;
use InvalidArgumentException;

/**
 * Kernel contract adapter: string type in, domain enum out.
 *
 * `silverspoon/billing` owns *when* to notify but not the notification catalog,
 * so it emits a type value (`service_subscription_grace`). This adapter is the
 * single place that string becomes a {@see NotificationType} — an unknown value
 * fails loudly instead of silently dropping the bell.
 */
final class TypeNotificationDispatcher implements NotificationDispatcher
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function emit(
        string $type,
        array $data = [],
        array $users = [],
        ?array $roles = null,
        ?string $actionRoute = null,
        array $actionParams = [],
    ): void {
        $resolved = $this->resolveType($type);

        $this->notifications->emit($resolved, $data, $roles, $users, $actionRoute, $actionParams);
    }

    private function resolveType(string $type): NotificationType
    {
        foreach (NotificationType::cases() as $case) {
            if ($case->value === $type) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unknown notification type [{$type}].");
    }
}
