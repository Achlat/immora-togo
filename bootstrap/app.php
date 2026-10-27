<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);
        $middleware->validateCsrfTokens(except: ['paiements/notification/*']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        // L'agence active doit être connue AVANT la liaison des modèles de route,
        // sinon la portée multi-agences ne filtrerait pas {property}, {lease}, etc.
        $middleware->appendToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\ResolveAgency::class,
        );
        $middleware->alias([
            'agency' => \App\Http\Middleware\ResolveAgency::class,
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'portal' => \App\Http\Middleware\EnsurePortalUser::class,
            'inactivity' => \App\Http\Middleware\InactivityTimeout::class,
            'two-factor' => \App\Http\Middleware\RequireTwoFactorSetup::class,
            'subscription' => \App\Http\Middleware\SubscriptionGate::class,
            'super-admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('immora:invoices')->monthlyOn(25, '06:00');
        $schedule->command('immora:penalties')->dailyAt('06:30');
        $schedule->command('immora:payments-check')->everyFifteenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
