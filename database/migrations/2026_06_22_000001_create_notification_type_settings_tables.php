<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function rolesTable(): string
    {
        return config('permission.table_names.roles', 'roles');
    }

    public function up(): void
    {
        Schema::create('notification_type_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Maps to NotificationType enum value (e.g. user_created).
            $table->string('type')->unique();
            // When true, the subject user id from event data is added to the personal audience.
            $table->boolean('notify_subject_user')->default(false);
            $table->timestamps();
        });

        Schema::create('notification_type_setting_roles', function (Blueprint $table) {
            $table->foreignUuid('setting_id')
                ->constrained('notification_type_settings')
                ->cascadeOnDelete();
            $table->foreignUuid('role_id')
                ->constrained($this->rolesTable())
                ->cascadeOnDelete();

            $table->primary(['setting_id', 'role_id']);
        });

        Schema::create('notification_type_setting_users', function (Blueprint $table) {
            $table->foreignUuid('setting_id')
                ->constrained('notification_type_settings')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->primary(['setting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_type_setting_users');
        Schema::dropIfExists('notification_type_setting_roles');
        Schema::dropIfExists('notification_type_settings');
    }
};
