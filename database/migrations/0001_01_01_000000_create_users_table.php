<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('password_hash')->nullable();
            $table->string('full_name');
            $table->enum('role', ['employee', 'manager']);
            $table->string('pin_hash')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['google', 'apple']);
            $table->string('provider_uid');
            $table->unique(['provider', 'provider_uid']);
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->double('lat');
            $table->double('lng');
            $table->integer('geofence_radius_m')->default(100);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->string('label')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('punch_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('state', ['active', 'on_break', 'clocked_out'])->default('active');
            $table->timestamp('clocked_in_at');
            $table->timestamp('clocked_out_at')->nullable();
            $table->integer('work_seconds')->nullable();
            $table->integer('break_seconds')->nullable();
            $table->integer('overtime_seconds')->nullable();
            $table->enum('clock_in_method', ['qr', 'gps', 'selfie', 'manual']);
            $table->enum('clock_out_method', ['qr', 'gps', 'selfie', 'manual'])->nullable();
            $table->double('clock_in_lat')->nullable();
            $table->double('clock_in_lng')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'state']);
            $table->index(['site_id', 'clocked_in_at']);
        });

        Schema::create('break_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('punch_sessions')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('punch_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('punch_sessions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['clock_in', 'clock_out', 'break_start', 'break_end', 'manual_adj']);
            $table->timestamp('occurred_at');
            $table->string('method')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->string('photo_url')->nullable();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'occurred_at']);
        });

        Schema::create('saved_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('report_type', ['payroll', 'overtime', 'late', 'attendance', 'custom']);
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->string('schedule_cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['no_show', 'late', 'ot_request', 'ot_approved', 'shift_reminder', 'manual_adj', 'punch.clock_in', 'punch.clock_out', 'punch.break_start', 'punch.break_end']);
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('fcm_token')->unique();
            $table->enum('platform', ['android', 'ios']);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('punch_events');
        Schema::dropIfExists('break_records');
        Schema::dropIfExists('punch_sessions');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('oauth_accounts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('organizations');
    }
};
