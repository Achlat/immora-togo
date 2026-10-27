<?php

use App\Http\Controllers\Admin\AgencyAdminController;
use App\Http\Controllers\Auth\AgencySelectController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterAgencyController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/connexion');

/* ---------- Pages publiques ---------- */
Route::get('/verifier/{code}', [VerifyController::class, 'show'])->name('verify.show');
Route::post('/paiements/notification/{provider}', [OnlinePaymentController::class, 'notify'])->name('payments.notify');
Route::get('/paiements/retour/{reference}', [OnlinePaymentController::class, 'return'])->name('payments.return');
Route::get('/paiements/simulateur/{reference}', [OnlinePaymentController::class, 'simulator'])->name('payments.simulator');
Route::post('/paiements/simulateur/{reference}', [OnlinePaymentController::class, 'simulate'])->name('payments.simulate');

/* ---------- Authentification (CDC chap. 8) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/connexion/code', [OtpLoginController::class, 'send'])->name('otp.send')->middleware('throttle:10,1');
    Route::get('/connexion/code', [OtpLoginController::class, 'show'])->name('otp.show');
    Route::post('/connexion/code/verifier', [OtpLoginController::class, 'verify'])->name('otp.verify')->middleware('throttle:20,1');
    Route::get('/double-authentification', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/double-authentification', [TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:10,1');
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reinitialiser/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reinitialiser/{token}', [PasswordResetController::class, 'update'])->name('password.update');
    Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
    Route::get('/essai-gratuit', [RegisterAgencyController::class, 'show'])->name('register');
    Route::post('/essai-gratuit', [RegisterAgencyController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/choisir-agence', [AgencySelectController::class, 'show'])->name('agency.select');
    Route::post('/choisir-agence', [AgencySelectController::class, 'store'])->name('agency.switch');
    Route::get('/securite/double-authentification', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/securite/double-authentification', [TwoFactorController::class, 'enable'])->name('two-factor.enable');

    /* ---------- Super-admin éditeur ---------- */
    Route::middleware('super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/agences', [AgencyAdminController::class, 'index'])->name('agencies.index');
        Route::post('/agences/{agency}/statut', [AgencyAdminController::class, 'status'])->name('agencies.status');
        Route::post('/agences/{agency}/prolonger', [AgencyAdminController::class, 'extend'])->name('agencies.extend');
    });

    /* ---------- Portails propriétaire / locataire ---------- */
    Route::middleware(['agency', 'portal', 'inactivity'])->prefix('espace')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'home'])->name('home');
        Route::post('/payer', [PortalController::class, 'pay'])->name('pay');
        Route::get('/echeances/{invoice}/pdf', [PortalController::class, 'invoicePdf'])->name('invoice.pdf');
        Route::get('/recus/{payment}/pdf', [PortalController::class, 'paymentPdf'])->name('payment.pdf');
        Route::get('/releves/{statement}/pdf', [PortalController::class, 'statementPdf'])->name('statement.pdf');
    });

    /* ---------- Back-office agence ---------- */
    Route::middleware(['agency', 'staff', 'inactivity', 'two-factor', 'subscription'])->group(function () {
        Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard')->middleware('can:dashboard.view');
        Route::get('/recherche', SearchController::class)->name('search');

        Route::middleware('can:properties.view')->group(function () {
            Route::get('/biens', [PropertyController::class, 'index'])->name('properties.index');
            Route::get('/biens/{property}', [PropertyController::class, 'show'])->name('properties.show')->whereNumber('property');
        });
        Route::middleware('can:properties.manage')->group(function () {
            Route::get('/biens/nouveau', [PropertyController::class, 'create'])->name('properties.create');
            Route::post('/biens', [PropertyController::class, 'store'])->name('properties.store');
            Route::get('/biens/{property}/modifier', [PropertyController::class, 'edit'])->name('properties.edit');
            Route::put('/biens/{property}', [PropertyController::class, 'update'])->name('properties.update');
            Route::post('/biens/{property}/lots', [UnitController::class, 'store'])->name('units.store');
            Route::get('/lots/{unit}/modifier', [UnitController::class, 'edit'])->name('units.edit');
            Route::put('/lots/{unit}', [UnitController::class, 'update'])->name('units.update');
        });

        Route::middleware('can:owners.manage')->group(function () {
            Route::resource('proprietaires', OwnerController::class)->parameters(['proprietaires' => 'owner'])
                ->names('owners')->except('destroy');
            Route::post('/proprietaires/{owner}/portail', [OwnerController::class, 'invitePortal'])->name('owners.portal');
        });

        Route::middleware('can:tenants.manage')->group(function () {
            Route::resource('locataires', TenantController::class)->parameters(['locataires' => 'tenant'])
                ->names('tenants')->except('destroy');
            Route::post('/locataires/{tenant}/portail', [TenantController::class, 'invitePortal'])->name('tenants.portal');
        });

        Route::middleware('can:leases.manage')->group(function () {
            Route::get('/baux', [LeaseController::class, 'index'])->name('leases.index');
            Route::get('/baux/nouveau', [LeaseController::class, 'create'])->name('leases.create');
            Route::post('/baux', [LeaseController::class, 'store'])->name('leases.store');
            Route::get('/baux/{lease}', [LeaseController::class, 'show'])->name('leases.show');
            Route::post('/baux/{lease}/activer', [LeaseController::class, 'activate'])->name('leases.activate');
            Route::post('/baux/{lease}/preavis', [LeaseController::class, 'notice'])->name('leases.notice');
            Route::post('/baux/{lease}/resilier', [LeaseController::class, 'terminate'])->name('leases.terminate');
        });

        Route::middleware('can:payments.collect')->group(function () {
            Route::get('/echeances', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/echeances/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/echeances/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::post('/echeances/{invoice}/relancer', [InvoiceController::class, 'remind'])->name('invoices.remind');
            Route::get('/encaissements', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/encaissements/nouveau', [PaymentController::class, 'create'])->name('payments.create');
            Route::post('/encaissements', [PaymentController::class, 'store'])->name('payments.store');
            Route::get('/encaissements/{payment}', [PaymentController::class, 'show'])->name('payments.show');
            Route::get('/encaissements/{payment}/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');
            Route::get('/caisse', [CashController::class, 'index'])->name('cash.index');
            Route::post('/caisse/ouvrir', [CashController::class, 'open'])->name('cash.open');
        });
        Route::post('/encaissements/{payment}/annuler', [PaymentController::class, 'reverse'])
            ->name('payments.reverse')->middleware('can:payments.reverse');
        Route::post('/caisse/{session}/cloturer', [CashController::class, 'close'])
            ->name('cash.close')->middleware('can:cash.close');

        Route::middleware('can:expenses.manage')->group(function () {
            Route::get('/depenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/depenses/nouvelle', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('/depenses', [ExpenseController::class, 'store'])->name('expenses.store');
        });

        Route::middleware('can:statements.manage')->group(function () {
            Route::get('/releves', [StatementController::class, 'index'])->name('statements.index');
            Route::post('/releves/generer', [StatementController::class, 'generate'])->name('statements.generate');
            Route::get('/releves/{statement}', [StatementController::class, 'show'])->name('statements.show');
            Route::get('/releves/{statement}/pdf', [StatementController::class, 'pdf'])->name('statements.pdf');
        });
        Route::middleware('can:payouts.validate')->group(function () {
            Route::post('/releves/{statement}/valider', [StatementController::class, 'validateStatement'])->name('statements.validate');
            Route::post('/releves/{statement}/reverser', [StatementController::class, 'payout'])->name('statements.payout');
        });

        Route::get('/rapports/impayes', [ReportController::class, 'arrears'])->name('reports.arrears')->middleware('can:reports.view');

        Route::middleware('can:team.manage')->group(function () {
            Route::get('/equipe', [TeamController::class, 'index'])->name('team.index');
            Route::post('/equipe/inviter', [TeamController::class, 'invite'])->name('team.invite');
            Route::post('/equipe/{user}/statut', [TeamController::class, 'toggle'])->name('team.toggle');
        });

        Route::middleware('can:settings.manage')->group(function () {
            Route::get('/parametres', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/parametres', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::get('/securite', [SecurityController::class, 'index'])->name('security.index');
        Route::post('/securite/sessions/revoquer', [SecurityController::class, 'revokeOthers'])->name('security.revoke-others');
        Route::post('/securite/sessions/{session}', [SecurityController::class, 'revoke'])->name('security.revoke');
    });
});
