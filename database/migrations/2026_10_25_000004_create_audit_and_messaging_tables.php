<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit et journal des messages SMS / WhatsApp / e-mail (CDC chap. 19 et 25).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->index();
            $table->foreignId('user_id')->nullable();
            $table->string('action', 60);
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at');
            $table->index(['agency_id', 'entity_type', 'entity_id']);
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->index();
            $table->string('channel', 15);
            $table->string('recipient', 190);
            $table->string('template', 60);
            $table->text('body');
            $table->string('status', 20)->default('sent');
            $table->string('provider_ref', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('audit_logs');
    }
};
