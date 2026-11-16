<?php

namespace App\Domain\Notification\Notifications;

/**
 * Catalogue of activity-notification types.
 *
 * Each type maps to a localized message (lang/{locale}/api/notifications.php
 * under `types.{type}`), a Bootstrap icon, and optional enum fallbacks when no
 * database setting exists. **Audience targeting** (roles + users) is stored in
 * `notification_type_settings` and managed via the admin UI — see
 * docs/domains/notification/README.md.
 *
 * Adding a new action = add a case here + a lang entry + fire
 * ActivityNotificationEvent from wherever the action happens. No engine change.
 */
enum NotificationType: string
{
    // Broadcast: a new user was created (seen by Root + Admin).
    case UserCreated = 'user_created';

    // Broadcast: an existing user was updated (seen by Root + Admin) — drives the
    // users list live reload. Distinct from AccountUpdated, which is personal.
    case UserUpdated = 'user_updated';

    // Broadcast: a user was deleted (seen by Root + Admin) — drives list reload.
    case UserDeleted = 'user_deleted';

    // Personal: a user's own account was updated (seen by that user only).
    case AccountUpdated = 'account_updated';

    // Personal: subscription entered grace — renew or excess files will be removed.
    case ServiceSubscriptionGrace = 'service_subscription_grace';

    // Personal: subscription expired; free-tier purge applied.
    case ServiceSubscriptionExpired = 'service_subscription_expired';

    // Broadcast: unregistered inference model billed at provisional rates — classify in catalog.
    case InferenceUnregisteredModel = 'inference_unregistered_model';

    /**
     * Bootstrap icon class rendered by the web bell.
     */
    public function icon(): string
    {
        return match ($this) {
            self::UserCreated => 'bi bi-person-plus',
            self::UserUpdated => 'bi bi-person-gear',
            self::UserDeleted => 'bi bi-person-x',
            self::AccountUpdated => 'bi bi-person-gear',
            self::ServiceSubscriptionGrace => 'bi bi-hourglass-split',
            self::ServiceSubscriptionExpired => 'bi bi-exclamation-triangle',
            self::InferenceUnregisteredModel => 'bi bi-cpu',
        };
    }

    /**
     * Fallback target roles when no database setting exists (e.g. before seeding).
     *
     * @return list<string>
     */
    public function defaultRoles(): array
    {
        return match ($this) {
            self::UserCreated,
            self::UserUpdated,
            self::UserDeleted => ['Root', 'Admin'],
            self::InferenceUnregisteredModel => ['Root', 'Admin', 'Management'],
            self::AccountUpdated,
            self::ServiceSubscriptionGrace,
            self::ServiceSubscriptionExpired => [],
        };
    }

    /**
     * Translation key for the human-readable message.
     */
    public function messageKey(): string
    {
        return "api/notifications.types.{$this->value}";
    }

    /**
     * DataTable ID that should reload when this notification fires. Null for
     * types that don't correspond to a list screen (e.g. personal updates).
     */
    public function tableId(): ?string
    {
        return match ($this) {
            self::UserCreated,
            self::UserUpdated,
            self::UserDeleted => 'users-table',
            self::AccountUpdated,
            self::ServiceSubscriptionGrace,
            self::ServiceSubscriptionExpired => null,
            self::InferenceUnregisteredModel => 'inference-model-cards-table',
        };
    }
}
