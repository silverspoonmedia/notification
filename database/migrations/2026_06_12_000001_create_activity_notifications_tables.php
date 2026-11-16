<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain "activity" notifications with two targeting modes:
 *
 *  - **Per role** (broadcast): linked to roles via `activity_notification_roles`.
 *    Any current/future holder of a target role sees it (e.g. "a new user was
 *    created" → Root + Admin).
 *  - **Per user** (personal): linked to specific users via
 *    `activity_notification_users` (e.g. "your export is ready").
 *
 * A notification may use either or both. A viewer sees it when they hold one of
 * its target roles OR are one of its target users. Per-user read state lives on
 * `activity_notification_reads`.
 *
 * Deliberately NOT named `notifications`: that table is reserved by Laravel's
 * Notifiable / database channel. Keeping our own surface under
 * `activity_notifications` avoids shadowing the framework feature.
 */
return new class extends Migration
{
    /**
     * The Spatie roles table name.
     */
    private function rolesTable(): string
    {
        return config('permission.table_names.roles', 'roles');
    }

    public function up(): void
    {
        Schema::create('activity_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Action key (e.g. "user_created"); drives the localized message + icon.
            $table->string('type');
            // Arbitrary context (subject name/email, actor, …) used as message
            // replacements and to render the deep link.
            $table->json('data')->nullable();
            // Optional deep-link: a REGISTERED route name + params, resolved by the
            // consumer (the web bell builds route(action_route, action_params)).
            $table->string('action_route')->nullable();
            $table->json('action_params')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });

        // Per-role audience (broadcast): a viewer sees a notification when they
        // hold ANY of these roles. New members of a role immediately see past ones.
        Schema::create('activity_notification_roles', function (Blueprint $table) {
            $table->foreignUuid('notification_id')
                ->constrained('activity_notifications')
                ->cascadeOnDelete();
            $table->foreignUuid('role_id')
                ->constrained($this->rolesTable())
                ->cascadeOnDelete();

            $table->primary(['notification_id', 'role_id']);
        });

        // Per-user audience (personal): a viewer sees a notification when they are
        // explicitly listed here.
        Schema::create('activity_notification_users', function (Blueprint $table) {
            $table->foreignUuid('notification_id')
                ->constrained('activity_notifications')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->primary(['notification_id', 'user_id']);
        });

        // Per-user read state. A present row means the user has read it.
        Schema::create('activity_notification_reads', function (Blueprint $table) {
            $table->foreignUuid('notification_id')
                ->constrained('activity_notifications')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();

            $table->primary(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notification_reads');
        Schema::dropIfExists('activity_notification_users');
        Schema::dropIfExists('activity_notification_roles');
        Schema::dropIfExists('activity_notifications');
    }
};
