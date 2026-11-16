<?php

namespace App\Domain\Notification\Tests\Unit\Repositories\Eloquent;

use App\Domain\Notification\Models\NotificationTypeSetting;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Notification\Notifications\NotificationType;
use App\Domain\Notification\Repositories\Eloquent\NotificationTypeSettingRepository;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTypeSettingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_resolve_audience_merges_roles_users_and_subject_user(): void
    {
        $repo = new NotificationTypeSettingRepository;

        $setting = NotificationTypeSetting::factory()->create([
            'type' => NotificationType::AccountUpdated->value,
            'notify_subject_user' => true,
        ]);

        $extraUser = User::factory()->create();
        $subjectUser = User::factory()->create();

        $setting->roles()->sync(
            Role::query()->whereIn('name', ['Admin'])->pluck('id'),
        );
        $setting->targetUsers()->sync([$extraUser->id]);

        $repo->flushCache();

        $audience = $repo->resolveAudience(
            NotificationType::AccountUpdated,
            ['subject_user_id' => $subjectUser->id],
        );

        $this->assertSame(['Admin'], $audience['roles']);
        $this->assertEqualsCanonicalizing(
            [$extraUser->id, $subjectUser->id],
            $audience['users'],
        );
    }

    public function test_resolve_audience_honours_explicit_role_override(): void
    {
        $repo = new NotificationTypeSettingRepository;

        NotificationTypeSetting::factory()->create([
            'type' => NotificationType::UserCreated->value,
        ])->roles()->sync(
            Role::query()->whereIn('name', ['Root'])->pluck('id'),
        );

        $repo->flushCache();

        $audience = $repo->resolveAudience(
            NotificationType::UserCreated,
            [],
            ['Moderator'],
        );

        $this->assertSame(['Moderator'], $audience['roles']);
    }

    /**
     * Regression: the snapshot cache must store plain primitives, not hydrated
     * Eloquent models. Caching model graphs in a persistent store can deserialize
     * into __PHP_Incomplete_Class and blow up resolveAudience with a TypeError.
     * A cache round-trip (rememberForever ➜ get) must still resolve cleanly.
     */
    public function test_resolve_audience_reads_from_cached_primitive_snapshot(): void
    {
        $repo = new NotificationTypeSettingRepository;

        $setting = NotificationTypeSetting::factory()->create([
            'type' => NotificationType::UserCreated->value,
            'notify_subject_user' => false,
        ]);
        $setting->roles()->sync(Role::query()->whereIn('name', ['Root', 'Admin'])->pluck('id'));

        $repo->flushCache();

        // First call populates the cache.
        $first = $repo->resolveAudience(NotificationType::UserCreated);

        // Second call (fresh repo instance) must read the cached snapshot and
        // still return the same primitive audience without touching models.
        $second = (new NotificationTypeSettingRepository)->resolveAudience(NotificationType::UserCreated);

        $cached = \Illuminate\Support\Facades\Cache::get('notification_type_settings.snapshot');

        $this->assertIsArray($cached, 'snapshot cache must hold a plain array');
        $this->assertIsArray($cached[NotificationType::UserCreated->value]['roles'] ?? null);
        $this->assertEqualsCanonicalizing(['Root', 'Admin'], $first['roles']);
        $this->assertEqualsCanonicalizing($first['roles'], $second['roles']);
    }
}
