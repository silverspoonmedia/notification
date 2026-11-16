<?php

namespace App\Domain\Notification\Repositories\Eloquent;

use App\Domain\Notification\Models\ActivityNotification;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Notification\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $attributes, array $roleIdsOrNames, array $userIds = []): ActivityNotification
    {
        return DB::transaction(function () use ($attributes, $roleIdsOrNames, $userIds): ActivityNotification {
            $notification = ActivityNotification::query()->create($attributes);

            $notification->roles()->sync($this->resolveRoleIds($roleIdsOrNames));
            $notification->targetUsers()->sync(array_values(array_unique($userIds)));

            return $notification->load(['roles', 'targetUsers']);
        });
    }

    public function listForViewer(User $viewer, int $limit = 50): Collection
    {
        return ActivityNotification::query()
            ->where(fn (Builder $q) => $this->scopeVisibleTo($q, $viewer))
            ->withCount(['readers as is_read' => fn (BuilderContract $q) => $q->where('user_id', $viewer->id)])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->each(fn (ActivityNotification $n) => $n->setAttribute('is_read', (bool) $n->getAttribute('is_read')));
    }

    public function unreadCountForViewer(User $viewer): int
    {
        return ActivityNotification::query()
            ->where(fn (Builder $q) => $this->scopeVisibleTo($q, $viewer))
            ->whereDoesntHave('readers', fn (BuilderContract $q) => $q->where('user_id', $viewer->id))
            ->count();
    }

    public function isVisibleTo(ActivityNotification $notification, User $viewer): bool
    {
        return ActivityNotification::query()
            ->whereKey($notification->getKey())
            ->where(fn (Builder $q) => $this->scopeVisibleTo($q, $viewer))
            ->exists();
    }

    public function markAsRead(ActivityNotification $notification, User $viewer): void
    {
        $notification->readers()->syncWithoutDetaching([
            $viewer->id => ['read_at' => now()],
        ]);
    }

    public function recipientUserIds(ActivityNotification $notification): array
    {
        $notification->loadMissing(['roles', 'targetUsers']);

        $roleIds = $notification->roles->pluck('id')->all();

        $roleUserIds = $roleIds === []
            ? []
            : User::query()
                ->whereHas('roles', fn (BuilderContract $q) => $q->whereIn('roles.id', $roleIds))
                ->pluck('id')
                ->all();

        $directUserIds = $notification->targetUsers->pluck('id')->all();

        return array_values(array_unique([...$roleUserIds, ...$directUserIds]));
    }

    public function markAllAsRead(User $viewer): int
    {
        $unread = ActivityNotification::query()
            ->where(fn (Builder $q) => $this->scopeVisibleTo($q, $viewer))
            ->whereDoesntHave('readers', fn (BuilderContract $q) => $q->where('user_id', $viewer->id))
            ->pluck('id');

        if ($unread->isEmpty()) {
            return 0;
        }

        $now = now();

        // Insert pivot rows directly. We avoid a User relation method here because
        // Laravel's Notifiable trait already owns `notifications()` / `readNotifications()`.
        DB::table('activity_notification_reads')->insert(
            $unread->map(fn (string $id): array => [
                'notification_id' => $id,
                'user_id' => $viewer->id,
                'read_at' => $now,
            ])->all(),
        );

        return $unread->count();
    }

    /**
     * Constrain a query to notifications visible to the viewer: the viewer holds
     * one of the target roles (broadcast) OR is an explicitly targeted user
     * (personal).
     */
    private function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        $roleIds = $this->viewerRoleIds($viewer);

        return $query->where(function (Builder $q) use ($viewer, $roleIds): void {
            if ($roleIds !== []) {
                $q->whereHas('roles', fn (BuilderContract $r) => $r->whereIn('roles.id', $roleIds));
            }

            $q->orWhereHas('targetUsers', fn (BuilderContract $u) => $u->where('users.id', $viewer->id));
        });
    }

    /**
     * Resolve a mixed list of role ids or names to concrete role ids (api guard).
     *
     * @param  list<string>  $idsOrNames
     * @return list<string>
     */
    private function resolveRoleIds(array $idsOrNames): array
    {
        if ($idsOrNames === []) {
            return [];
        }

        return Role::query()
            ->where('guard_name', 'api')
            ->where(function (Builder $q) use ($idsOrNames): void {
                $q->whereIn('id', $idsOrNames)
                    ->orWhereIn('name', $idsOrNames);
            })
            ->pluck('id')
            ->all();
    }

    /**
     * The viewer's role ids on the api guard.
     *
     * @return list<string>
     */
    private function viewerRoleIds(User $viewer): array
    {
        return $viewer->roles()->pluck('id')->all();
    }
}
