<?php

namespace App\Domain\Notification\Database\Seeders;

use App\Domain\Identity\Models\Role;
use App\Domain\Notification\Models\NotificationTypeSetting;
use App\Domain\Notification\Notifications\NotificationType;
use App\Domain\Notification\Repositories\Contracts\NotificationTypeSettingRepositoryInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds default per-type notification audiences (replaces hardcoded enum defaults).
 */
class NotificationTypeSettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Broadcast list-change signals seen by Root + Admin (drive DataTable reload).
        $this->broadcastToAdmins(NotificationType::UserCreated);
        $this->broadcastToAdmins(NotificationType::UserUpdated);
        $this->broadcastToAdmins(NotificationType::UserDeleted);

        // Personal types: subject user only. Sync roles([]) idempotently so any
        // stray role pivots (data pollution) get cleared — a personal note must
        // never broadcast to admin roles.
        $this->personal(NotificationType::AccountUpdated);
        $this->personal(NotificationType::ServiceSubscriptionGrace);
        $this->personal(NotificationType::ServiceSubscriptionExpired);

        $this->broadcastToRoles(NotificationType::InferenceUnregisteredModel, ['Root', 'Admin', 'Management']);

        app(NotificationTypeSettingRepositoryInterface::class)->flushCache();
    }

    private function broadcastToAdmins(NotificationType $type): void
    {
        $setting = NotificationTypeSetting::query()->firstOrCreate(
            ['type' => $type->value],
            ['notify_subject_user' => false],
        );

        $setting->roles()->sync(
            Role::query()
                ->where('guard_name', 'api')
                ->whereIn('name', ['Root', 'Admin'])
                ->pluck('id')
                ->all(),
        );
    }

    private function personal(NotificationType $type): void
    {
        $setting = NotificationTypeSetting::query()->firstOrCreate(
            ['type' => $type->value],
            ['notify_subject_user' => true],
        );

        $setting->roles()->sync([]);
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function broadcastToRoles(NotificationType $type, array $roleNames): void
    {
        $setting = NotificationTypeSetting::query()->firstOrCreate(
            ['type' => $type->value],
            ['notify_subject_user' => false],
        );

        $setting->roles()->sync(
            Role::query()
                ->where('guard_name', 'api')
                ->whereIn('name', $roleNames)
                ->pluck('id')
                ->all(),
        );
    }
}
