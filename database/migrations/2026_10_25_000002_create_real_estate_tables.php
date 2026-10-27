<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patrimoine, propriétaires, locataires et baux (CDC chap. 10 à 12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('reference', 30);
            $table->string('kind', 10)->default('person');
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('phone_e164', 20)->nullable();
            $table->string('whatsapp_e164', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('country_of_residence', 2)->nullable();
            $table->string('id_document', 80)->nullable();
            $table->string('payout_method', 20)->default('transfer');
            $table->string('payout_details', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agency_id', 'reference']);
        });

        Schema::create('mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('owner_id')->constrained();
            $table->string('reference', 30);
            $table->string('kind', 20)->default('management');
            $table->date('start_date');
            $table->unsignedSmallInteger('duration_months')->default(12);
            $table->boolean('tacit_renewal')->default(true);
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->boolean('commission_vat')->default(true);
            $table->string('commission_base', 20)->default('rent');
            $table->bigInteger('expense_ceiling_amount')->default(100000);
            $table->bigInteger('reserve_amount')->default(0);
            $table->unsignedTinyInteger('payout_day')->default(10);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['agency_id', 'reference']);
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('owner_id')->nullable()->constrained();
            $table->string('reference', 30);
            $table->string('name', 150);
            $table->string('type', 30);
            $table->string('city', 80);
            $table->string('district', 120)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('land_title', 80)->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agency_id', 'reference']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('property_id')->constrained();
            $table->string('reference', 40);
            $table->string('label', 60);
            $table->string('type', 30);
            $table->string('usage_type', 20)->default('habitation');
            $table->string('floor', 20)->nullable();
            $table->decimal('surface_m2', 8, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->boolean('furnished')->default(false);
            $table->bigInteger('base_rent_amount');
            $table->bigInteger('charges_amount')->default(0);
            $table->unsignedTinyInteger('deposit_months')->default(2);
            $table->unsignedTinyInteger('advance_months')->default(2);
            $table->string('electricity_meter', 40)->nullable();
            $table->string('water_meter', 40)->nullable();
            $table->string('status', 20)->default('vacant');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agency_id', 'reference']);
            $table->index(['agency_id', 'status']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('reference', 30);
            $table->string('kind', 10)->default('person');
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('phone_e164', 20)->nullable();
            $table->string('whatsapp_e164', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('id_document', 80)->nullable();
            $table->string('profession', 120)->nullable();
            $table->string('employer', 150)->nullable();
            $table->bigInteger('monthly_income_amount')->nullable();
            $table->string('emergency_contact', 190)->nullable();
            $table->string('guarantor_name', 150)->nullable();
            $table->string('guarantor_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agency_id', 'reference']);
            $table->index(['agency_id', 'phone_e164']);
        });

        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->string('reference', 30);
            $table->string('type', 20)->default('habitation');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_months')->default(12);
            $table->bigInteger('rent_amount');
            $table->bigInteger('charges_amount')->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('due_day')->default(5);
            $table->unsignedTinyInteger('grace_days')->default(5);
            $table->decimal('penalty_rate', 5, 2)->default(0);
            $table->bigInteger('deposit_amount')->default(0);
            $table->bigInteger('advance_amount')->default(0);
            $table->bigInteger('agency_fee_amount')->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('notice_date')->nullable();
            $table->date('terminated_at')->nullable();
            $table->text('special_terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->unique(['agency_id', 'reference']);
            $table->index(['unit_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['leases', 'tenants', 'units', 'properties', 'mandates', 'owners'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
