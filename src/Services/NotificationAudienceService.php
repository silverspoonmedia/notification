<?php

namespace App\Domain\Notification\Services;

use App\Domain\Identity\Models\Role;
use App\Domain\Notification\Models\NotificationTypeSetting;
use App\Domain\Notification\Notifications\NotificationType;
use App\Domain\Notification\Repositories\Contracts\NotificationTypeSettingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NotificationAudienceService
{
    public function __construct(
        private readonly NotificationTypeSettingRepositoryInterface $settings,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listPresented(): Collection
    {
        $byType = $this->settings->listAll()->keyBy('type');

        return collect(NotificationType::cases())->map(function (NotificationType $enum) use ($byType): array {
            $setting = $byType->get($enum->value);

            return $this->present($enum, $setting);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function presentType(string $type): array
    {
        $enum = NotificationType::tryFrom($type);

        if ($enum === null) {
            throw ValidationException::withMessages([
                'type' => [__('api/notifications.audiences.unknown_type')],
            ]);
        }

        return $this->present($enum, $this->settings->findByType($type));
    }

    /**
     * @param  list<string>  $roleNames
     * @param  list<string>  $userIds
     * @return array<string, mixed>
     */
    public function update(string $type, bool $notifySubjectUser, array $roleNames, array $userIds): array
    {
        $enum = NotificationType::tryFrom($type);

        if ($enum === null) {
            throw ValidationException::withMessages([
                'type' => [__('api/notifications.audiences.unknown_type')],
            ]);
        }

        $setting = $this->settings->findByType($type);

        if ($setting === null) {
            $setting = NotificationTypeSetting::query()->create([
                'type' => $type,
                'notify_subject_user' => $notifySubjectUser,
            ]);
        } else {
            $setting->forceFill(['notify_subject_user' => $notifySubjectUser])->save();
        }

        $this->settings->syncRoles($setting, $roleNames);
        $this->settings->syncUsers($setting, $userIds);

        return $this->present($enum, $setting->fresh(['roles', 'targetUsers']));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(NotificationType $enum, ?NotificationTypeSetting $setting): array
    {
        return [
            'type' => $enum->value,
            'label' => __($enum->messageKey()),
            'icon' => $enum->icon(),
            'notify_subject_user' => (bool) ($setting?->notify_subject_user ?? false),
            'roles' => $setting?->roles->pluck('name')->values()->all() ?? $enum->defaultRoles(),
            'users' => $setting?->targetUsers->pluck('id')->values()->all() ?? [],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function assignableRoles(): array
    {
        return Role::query()
            ->where('guard_name', 'api')
            ->orderBy('level')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => ['id' => (string) $role->id, 'name' => $role->name])
            ->values()
            ->all();
    }
}
