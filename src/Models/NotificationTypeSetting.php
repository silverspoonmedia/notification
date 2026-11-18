<?php

namespace App\Domain\Notification\Models;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Notification\Database\Factories\NotificationTypeSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationTypeSetting extends Model
{
    /** @use HasFactory<NotificationTypeSettingFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): NotificationTypeSettingFactory
    {
        return NotificationTypeSettingFactory::new();
    }

    protected $fillable = [
        'type',
        'notify_subject_user',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_subject_user' => 'boolean',
        ];
    }

    /**
     * Roles that receive broadcast notifications for this type.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'notification_type_setting_roles', 'setting_id', 'role_id');
    }

    /**
     * Users who always receive this notification type (personal audience).
     */
    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_type_setting_users', 'setting_id', 'user_id');
    }
}
