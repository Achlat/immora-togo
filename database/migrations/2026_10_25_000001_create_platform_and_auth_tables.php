<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle SaaS et authentification (CDC chap. 8, 20 et 23).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 60);
            $table->bigInteger('monthly_price_amount');
            $table->bigInteger('yearly_price_amount');
            $table->unsignedInteger('max_units')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->json('features');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->string('name', 150);
            $table->string('slug', 60)->unique();
            $table->string('custom_domain', 190)->nullable()->unique();
            $table->char('country_code', 2)->default('CI');
            $table->char('currency', 3)->default('XOF');
            $table->string('phone', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('rccm', 60)->nullable();
            $table->string('tax_id', 60)->nullable();
            $table->string('license_number', 60)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 30)->default('trial');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('plan_id')->constrained();
            $table->string('billing_cycle', 10)->default('monthly');
            $table->string('status', 20)->default('trial');
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_end')->nullable();
            $table->timestamps();
            $table->index(['agency_id', 'status']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 190)->nullable()->unique();
            $table->string('phone_e164', 20)->nullable()->unique();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('failed_logins')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('last_agency_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agency_user', function (Blueprint $table) {
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('role', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['agency_id', 'user_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('purpose', 30);
            $table->string('channel', 15);
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose', 'expires_at']);
        });

        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->char('token_hash', 64);
            $table->string('label', 190)->nullable();
            $table->timestamp('two_factor_trusted_until')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'token_hash']);
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 190);
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45);
            $table->boolean('success');
            $table->string('reason', 40)->nullable();
            $table->dateTime('created_at');
            $table->index(['identifier', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        Schema::create('auth_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('agency_id')->nullable()->index();
            $table->string('type', 40);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('created_at');
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('invited_by')->constrained('users');
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 190)->nullable();
            $table->string('phone_e164', 20)->nullable();
            $table->string('role', 30);
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['invitations', 'auth_events', 'login_attempts', 'trusted_devices', 'otp_codes',
            'password_resets', 'sessions', 'agency_user', 'users', 'subscriptions', 'agencies', 'plans'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
