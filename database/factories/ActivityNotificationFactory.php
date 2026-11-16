<?php

namespace App\Domain\Notification\Database\Factories;

use App\Domain\Notification\Models\ActivityNotification;
use App\Domain\Notification\Notifications\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityNotification>
 */
class ActivityNotificationFactory extends Factory
{
    protected $model = ActivityNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => NotificationType::UserCreated->value,
            'data' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
                'actor' => $this->faker->name(),
                'role' => 'User',
            ],
            'action_route' => 'users.edit',
            'action_params' => ['user' => $this->faker->uuid()],
        ];
    }
}
