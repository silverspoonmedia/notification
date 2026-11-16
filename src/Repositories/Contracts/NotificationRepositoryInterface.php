<?php

namespace App\Domain\Notification\Repositories\Contracts;

use App\Domain\Notification\Models\ActivityNotification;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Persist a notification and attach its audience: target roles (by id or
     * name) and/or explicitly targeted users.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $roleIdsOrNames
     * @param  list<string>  $userIds
     */
    public function create(array $attributes, array $roleIdsOrNames, array $userIds = []): ActivityNotification;

    /**
     * Notifications visible to the viewer (they hold at least one target role,
     * OR are an explicitly targeted user), newest first. Each model carries a
     * `is_read` boolean for the viewer.
     *
     * @return Collection<int, ActivityNotification>
     */
    public function listForViewer(User $viewer, int $limit = 50): Collection;

    /**
     * Count notifications the viewer has not yet read.
     */
    public function unreadCountForViewer(User $viewer): int;

    /**
     * Whether the viewer is allowed to see this notification (role overlap OR
     * direct user target).
     */
    public function isVisibleTo(ActivityNotification $notification, User $viewer): bool;

    /**
     * Mark a single notification read for the viewer (idempotent).
     */
    public function markAsRead(ActivityNotification $notification, User $viewer): void;

    /**
     * Mark every notification visible to the viewer as read. Returns the count
     * of notifications newly marked.
     */
    public function markAllAsRead(User $viewer): int;

    /**
     * User ids that should receive a realtime ping for this notification
     * (role holders plus explicit targets).
     *
     * @return list<string>
     */
    public function recipientUserIds(ActivityNotification $notification): array;
}
