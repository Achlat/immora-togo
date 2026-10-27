<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\LeaseBillingService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PenaltyService;
use App\Services\Billing\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsAgency;
use Tests\TestCase;

/**
 * Règles de gestion du CDC (chap. 21) vérifiées avec les exemples chiffrés du document.
 */
final class BillingRulesTest extends TestCase
{
    use BuildsAgency, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_entry_invoice_matches_rg02_and_rg03_example(): void
    {
        Carbon::setTestNow('2026-11-12 09:00');
        $agency = $this->makeAgency();
        ['lease' => $lease] = $this->makeLease($agency, '2026-11-12');

        app(LeaseBillingService::class)->activate($lease);

        $entry = Invoice::query()->where('lease_id', $lease->id)->where('kind', 'entry')->firstOrFail();
        $rent = $entry->lines->firstWhere('type', 'rent');

        $this->assertSame(95000, $rent->amount, 'RG-02 : 150 000 × 19 / 30 = 95 000');
        $this->assertSame(845000, $entry->total_amount, 'RG-03 : 300 000 + 300 000 + 95 000 + 150 000');
        $this->assertSame('occupied', $lease->unit->refresh()->status);
    }

    public function test_payment_settles_entry_then_advance_pays_following_months(): void
    {
        Carbon::setTestNow('2026-11-12 09:00');
        $agency = $this->makeAgency();
        ['lease' => $lease, 'tenant' => $tenant] = $this->makeLease($agency, '2026-11-12');
        $billing = app(LeaseBillingService::class);
        $billing->activate($lease);

        $payment = app(PaymentService::class)->record($tenant, 845000, 'cash');

        $entry = Invoice::query()->where('kind', 'entry')->firstOrFail();
        $this->assertSame('paid', $entry->status);
        $this->assertStringStartsWith('Q-', (string) $entry->receipt_number);
        $this->assertStringStartsWith('REC-', $payment->number);
        $this->assertSame(300000, $tenant->availableCredit(), 'Avance de 2 mois disponible');

        Carbon::setTestNow('2026-11-25 06:00');
        $december = $billing->createMonthlyInvoice($lease->refresh(), Carbon::parse('2026-12-01'));
        $this->assertSame('paid', $december->status, 'L\'avance règle automatiquement décembre');
        $this->assertSame(150000, $tenant->availableCredit());
    }

    public function test_partial_payment_issues_receipt_without_quittance_and_penalty_rg05(): void
    {
        Carbon::setTestNow('2026-10-01 08:00');
        $agency = $this->makeAgency();
        ['lease' => $lease, 'tenant' => $tenant] = $this->makeLease($agency, '2026-09-01', 150000, ['advance_amount' => 0, 'deposit_amount' => 0, 'agency_fee_amount' => 0]);
        $billing = app(LeaseBillingService::class);
        $billing->activate($lease);
        $october = Invoice::query()->where('kind', 'rent')->whereDate('period_start', '2026-10-01')->firstOrFail();
        Invoice::query()->where('kind', 'entry')->update(['status' => 'paid']);

        app(PaymentService::class)->record($tenant, 100000, 'mobile_money', ['provider' => 'wave', 'external_ref' => 'W-1']);
        $october->refresh();
        $this->assertSame('partial', $october->status);
        $this->assertNull($october->receipt_number, 'Pas de quittance pour un paiement partiel (RENT-03)');

        Carbon::setTestNow('2026-10-11 07:00');
        $applied = app(PenaltyService::class)->apply(now());
        $this->assertSame(1, $applied);
        $this->assertSame(5000, $october->refresh()->lines()->where('type', 'penalty')->value('amount'), 'RG-05 : 10 % de 50 000');

        $this->assertSame(0, app(PenaltyService::class)->apply(now()), 'Une seule pénalité par échéance');
    }

    public function test_reversal_reopens_invoice_and_cancels_quittance(): void
    {
        Carbon::setTestNow('2026-10-01 08:00');
        $agency = $this->makeAgency();
        ['lease' => $lease, 'tenant' => $tenant] = $this->makeLease($agency, '2026-10-01', 150000, ['advance_amount' => 0, 'deposit_amount' => 0, 'agency_fee_amount' => 0]);
        app(LeaseBillingService::class)->activate($lease);
        $payments = app(PaymentService::class);

        $payment = $payments->record($tenant, 150000, 'cheque');
        $invoice = Invoice::query()->where('lease_id', $lease->id)->firstOrFail();
        $this->assertSame('paid', $invoice->status);

        $reversal = $payments->reverse($payment, 'Chèque rejeté par la banque');

        $this->assertSame(-150000, $reversal->amount);
        $this->assertSame('reversed', $payment->refresh()->status);
        $this->assertSame(0, $invoice->refresh()->paid_amount);
        $this->assertNull($invoice->receipt_number);
        $this->assertSame(2, Payment::query()->count(), 'Aucune suppression : contre-écriture');
    }

    public function test_owner_statement_matches_rg06_logic(): void
    {
        Carbon::setTestNow('2026-11-01 08:00');
        $agency = $this->makeAgency();
        ['lease' => $lease, 'tenant' => $tenant, 'owner' => $owner] = $this->makeLease($agency, '2026-11-01', 1200000, ['advance_amount' => 0, 'deposit_amount' => 0, 'agency_fee_amount' => 0]);
        app(LeaseBillingService::class)->activate($lease);
        app(PaymentService::class)->record($tenant, 1200000, 'transfer', ['paid_at' => '2026-11-05 10:00']);
        Expense::create(['agency_id' => $agency->id, 'owner_id' => $owner->id, 'charged_to' => 'owner', 'category' => 'plomberie',
            'label' => 'Réparation plomberie A03', 'amount' => 85000, 'spent_on' => '2026-11-18', 'status' => 'approved']);

        $admin = $this->makeMember($agency);
        $statement = app(StatementService::class)->generate($owner->refresh(), Carbon::parse('2026-11-01'), $admin);

        $this->assertSame(1200000, $statement->collected_amount);
        $this->assertSame(120000, $statement->commission_amount);
        $this->assertSame(21600, $statement->commission_vat_amount);
        $this->assertSame(923400, $statement->payout_amount, 'RG-06 : exemple du cahier des charges');
        $this->assertSame(50000, $statement->closing_balance_amount, 'La réserve est reportée');
    }

    public function test_dual_control_blocks_self_validation_above_threshold(): void
    {
        Carbon::setTestNow('2026-11-01 08:00');
        $agency = $this->makeAgency();
        ['lease' => $lease, 'tenant' => $tenant, 'owner' => $owner] = $this->makeLease($agency, '2026-11-01', 1500000, ['advance_amount' => 0, 'deposit_amount' => 0, 'agency_fee_amount' => 0]);
        app(LeaseBillingService::class)->activate($lease);
        app(PaymentService::class)->record($tenant, 1500000, 'transfer', ['paid_at' => '2026-11-05 10:00']);
        $accountant = $this->makeMember($agency, 'accountant');
        $service = app(StatementService::class);
        $statement = $service->generate($owner->refresh(), Carbon::parse('2026-11-01'), $accountant);

        $this->expectException(\DomainException::class);
        $service->validate($statement, $accountant);
    }

    public function test_overlapping_lease_on_same_unit_is_refused(): void
    {
        Carbon::setTestNow('2026-10-01 08:00');
        $agency = $this->makeAgency();
        ['lease' => $lease] = $this->makeLease($agency, '2026-10-01');
        $billing = app(LeaseBillingService::class);
        $billing->activate($lease);

        $second = $lease->replicate(['reference', 'status', 'activated_at']);
        $second->fill(['reference' => 'BAIL-X', 'status' => 'draft', 'start_date' => '2026-12-01'])->save();

        $this->expectException(\DomainException::class);
        $billing->activate($second);
    }
}
