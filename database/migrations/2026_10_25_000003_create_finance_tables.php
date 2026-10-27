<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Échéances, encaissements, caisse, dépenses, relevés et reversements (CDC chap. 13, 14 et 21).
 * Montants en FCFA entiers (BIGINT). Aucune écriture financière n'est supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->foreignId('agency_id')->constrained();
            $table->string('name', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->primary(['agency_id', 'name', 'year']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('lease_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->string('number', 30);
            $table->string('kind', 20)->default('rent');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->string('status', 20)->default('due');
            $table->string('receipt_number', 30)->nullable();
            $table->string('verify_code', 16)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['agency_id', 'number']);
            $table->unique(['lease_id', 'kind', 'period_start']);
            $table->index(['agency_id', 'status', 'due_date']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->string('type', 20);
            $table->string('label', 190);
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->unsignedTinyInteger('priority')->default(5);
            $table->timestamps();
        });

        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->bigInteger('opening_amount')->default(0);
            $table->bigInteger('expected_amount')->nullable();
            $table->bigInteger('counted_amount')->nullable();
            $table->json('denominations')->nullable();
            $table->string('gap_reason', 255)->nullable();
            $table->dateTime('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['agency_id', 'user_id', 'closed_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->string('number', 30);
            $table->bigInteger('amount');
            $table->string('method', 20);
            $table->string('provider', 30)->nullable();
            $table->string('external_ref', 100)->nullable();
            $table->string('reference_note', 190)->nullable();
            $table->foreignId('cash_session_id')->nullable()->constrained();
            $table->dateTime('paid_at');
            $table->string('status', 20)->default('confirmed');
            $table->foreignId('reversal_of_id')->nullable()->constrained('payments');
            $table->string('reversal_reason', 255)->nullable();
            $table->string('verify_code', 16)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['agency_id', 'number']);
            $table->unique(['provider', 'external_ref']);
            $table->index(['agency_id', 'paid_at']);
        });

        Schema::create('tenant_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('lease_id')->nullable()->constrained();
            $table->foreignId('source_payment_id')->nullable()->constrained('payments');
            $table->string('source', 20);
            $table->bigInteger('amount');
            $table->bigInteger('remaining_amount');
            $table->timestamps();
            $table->index(['tenant_id', 'remaining_amount']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->foreignId('tenant_credit_id')->nullable()->constrained();
            $table->foreignId('invoice_line_id')->constrained();
            $table->bigInteger('amount');
            $table->dateTime('allocated_at');
            $table->timestamps();
            $table->index(['agency_id', 'allocated_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('owner_id')->nullable()->constrained();
            $table->foreignId('property_id')->nullable()->constrained();
            $table->foreignId('unit_id')->nullable()->constrained();
            $table->string('charged_to', 20)->default('owner');
            $table->string('category', 40);
            $table->string('label', 190);
            $table->bigInteger('amount');
            $table->date('spent_on');
            $table->string('supplier', 150)->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 20)->default('approved');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['agency_id', 'owner_id', 'spent_on']);
        });

        Schema::create('owner_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('owner_id')->constrained();
            $table->string('number', 30);
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('opening_balance_amount')->default(0);
            $table->bigInteger('collected_amount')->default(0);
            $table->bigInteger('commission_amount')->default(0);
            $table->bigInteger('commission_vat_amount')->default(0);
            $table->bigInteger('expenses_amount')->default(0);
            $table->bigInteger('reserve_amount')->default(0);
            $table->bigInteger('payout_amount')->default(0);
            $table->bigInteger('closing_balance_amount')->default(0);
            $table->json('details');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['agency_id', 'number']);
            $table->unique(['owner_id', 'period_start']);
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('owner_id')->constrained();
            $table->foreignId('owner_statement_id')->constrained();
            $table->bigInteger('amount');
            $table->string('method', 20);
            $table->string('reference_note', 190)->nullable();
            $table->dateTime('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->string('reference', 40)->unique();
            $table->string('provider', 30);
            $table->bigInteger('amount');
            $table->bigInteger('fee_amount')->default(0);
            $table->string('status', 20)->default('pending');
            $table->string('external_ref', 100)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->json('provider_payload')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['payment_intents', 'payouts', 'owner_statements', 'expenses', 'payment_allocations',
            'tenant_credits', 'payments', 'cash_sessions', 'invoice_lines', 'invoices', 'sequences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
