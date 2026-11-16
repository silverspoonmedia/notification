<?php

namespace App\Domain\Notification\Database\Factories;

use App\Domain\Notification\Models\NotificationTypeSetting;
use App\Domain\Notification\Notifications\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTypeSetting>
 */
class NotificationTypeSettingFactory extends Factory
{
    protected $model = NotificationTypeSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => NotificationType::UserCreated->value,
            'notify_subject_user' => false,
        ];
    }
}
