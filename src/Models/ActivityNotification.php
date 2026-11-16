<?php

namespace App\Domain\Notification\Models;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;

use App\Domain\Notification\Database\Factories\ActivityNotificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A role- and/or user-targeted activity notification.
 *
 * Two targeting modes (either or both):
 *  - **Per role** (broadcast) via `activity_notification_roles` — any current or
 *    future holder of a target role sees it.
 *  - **Per user** (personal) via `activity_notification_users` — only the listed
 *    users see it.
 *
 * Per-user read state lives on the `activity_notification_reads` pivot
 * (`read_at`).
 *
 * Not to be confused with Laravel's database-channel notifications (the
 * `notifications` table via the Notifiable trait); this is an app-domain
 * feature with its own RBAC visibility rules.
 */
class ActivityNotification extends Model
{
    /** @use HasFactory<ActivityNotificationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return ActivityNotificationFactory
     */
    protected static function newFactory(): ActivityNotificationFactory
    {
        return ActivityNotificationFactory::new();
    }

    protected $table = 'activity_notifications';

    protected $fillable = [
        'type',
        'data',
        'action_route',
        'action_params',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'action_params' => 'array',
        ];
    }

    /**
     * Roles whose holders may see this notification (broadcast audience).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'activity_notification_roles', 'notification_id', 'role_id');
    }

    /**
     * Users explicitly targeted by this notification (personal audience).
     */
    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_notification_users', 'notification_id', 'user_id');
    }

    /**
     * Users who have read this notification (pivot carries `read_at`).
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_notification_reads', 'notification_id', 'user_id')
            ->withPivot('read_at');
    }
}
