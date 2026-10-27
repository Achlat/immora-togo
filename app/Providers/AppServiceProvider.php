<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Messaging\LogSmsSender;
use App\Services\Messaging\SmsSender;
use App\Services\Payments\CinetPayGateway;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Support\CurrentAgency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentAgency::class);

        $this->app->bind(SmsSender::class, LogSmsSender::class);

        $this->app->bind(PaymentGateway::class, function () {
            return match (config('immora.payments.driver')) {
                'cinetpay' => new CinetPayGateway(config('immora.payments.cinetpay')),
                default => new FakeGateway,
            };
        });
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');
        Route::resourceVerbs(['create' => 'nouveau', 'edit' => 'modifier']);

        foreach (array_keys(config('immora.permissions')) as $permission) {
            Gate::define($permission, fn () => app(CurrentAgency::class)->can($permission));
        }

        Blade::directive('fcfa', fn (string $expression) => "<?php echo e(\\App\\Support\\Money::format((int) ({$expression}))); ?>");

        $this->app['auth']->guard('web')->setRememberDuration(60 * 24 * 30);
    }
}
