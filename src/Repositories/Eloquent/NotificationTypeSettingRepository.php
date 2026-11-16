<?php

namespace App\Domain\Notification\Repositories\Eloquent;

use App\Domain\Notification\Models\NotificationTypeSetting;
use App\Domain\Identity\Models\Role;
use App\Domain\Notification\Notifications\NotificationType;
use App\Domain\Notification\Repositories\Contracts\NotificationTypeSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationTypeSettingRepository implements NotificationTypeSettingRepositoryInterface
{
    private const CACHE_KEY = 'notification_type_settings.snapshot';

    /**
     * @return Collection<int, NotificationTypeSetting>
     */
    public function listAll(): Collection
    {
        return $this->freshSettings()->values();
    }

    public function findByType(string $type): ?NotificationTypeSetting
    {
        return $this->freshSettings()->get($type);
    }

    public function read(string $type): NotificationTypeSetting
    {
        $setting = $this->findByType($type);

        if ($setting === null) {
            abort(404);
        }

        return $setting;
    }

    public function syncRoles(NotificationTypeSetting $setting, array $idsOrNames): void
    {
        if ($idsOrNames === []) {
            $setting->roles()->sync([]);
            $this->flushCache();

            return;
        }

        $roleIds = Role::query()
            ->where('guard_name', 'api')
            ->where(function (Builder $q) use ($idsOrNames): void {
                $q->whereIn('id', $idsOrNames)
                    ->orWhereIn('name', $idsOrNames);
            })
            ->pluck('id')
            ->all();

        $setting->roles()->sync($roleIds);
        $this->flushCache();
    }

    public function syncUsers(NotificationTypeSetting $setting, array $userIds): void
    {
        $setting->targetUsers()->sync(array_values(array_unique($userIds)));
        $this->flushCache();
    }

    public function resolveAudience(
        NotificationType $type,
        array $data = [],
        ?array $overrideRoles = null,
        array $overrideUsers = [],
    ): array {
        $setting = $this->snapshot()[$type->value] ?? null;

        if ($overrideRoles !== null) {
            $roles = $overrideRoles;
        } elseif ($setting !== null) {
            $roles = $setting['roles'];
        } else {
            $roles = $type->defaultRoles();
        }

        $users = array_values(array_unique($overrideUsers));

        if ($setting !== null) {
            $users = array_values(array_unique([
                ...$users,
                ...$setting['users'],
            ]));

            if ($setting['notify_subject_user']) {
                $subjectId = $data['subject_user_id'] ?? null;

                if (is_string($subjectId) && $subjectId !== '') {
                    $users[] = $subjectId;
                }
            }
        }

        return [
            'roles' => $roles,
            'users' => array_values(array_unique($users)),
        ];
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Cached audience snapshot as PLAIN PRIMITIVES keyed by type.
     *
     * We deliberately never cache hydrated Eloquent models here. A persistent
     * cache store (database/redis) serializes whatever it's given; storing model
     * graphs means a class/relation change (or an autoload mismatch on another
     * host) deserializes into __PHP_Incomplete_Class, which then violates the
     * Collection return type and throws a TypeError. Primitives are immune.
     *
     * @return array<string, array{notify_subject_user: bool, roles: list<string>, users: list<string>}>
     */
    private function snapshot(): array
    {
        /** @var array<string, array{notify_subject_user: bool, roles: list<string>, users: list<string>}> $snapshot */
        $snapshot = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return $this->freshSettings()
                ->map(fn (NotificationTypeSetting $setting): array => [
                    'notify_subject_user' => (bool) $setting->notify_subject_user,
                    'roles' => $setting->roles->pluck('name')->values()->all(),
                    'users' => $setting->targetUsers->pluck('id')->values()->all(),
                ])
                ->all();
        });

        return $snapshot;
    }

    /**
     * Fresh, uncached settings keyed by type (with relations eager loaded).
     *
     * @return Collection<string, NotificationTypeSetting>
     */
    private function freshSettings(): Collection
    {
        return NotificationTypeSetting::query()
            ->with(['roles', 'targetUsers'])
            ->get()
            ->keyBy('type');
    }
}
