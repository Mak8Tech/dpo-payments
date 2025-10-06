<?php

namespace Mak8Tech\DpoPayments;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Mak8Tech\DpoPayments\Services\DpoService;
use Mak8Tech\DpoPayments\Services\PaymentService;
use Mak8Tech\DpoPayments\Services\SubscriptionService;
use Mak8Tech\DpoPayments\Services\CountryService;
use Mak8Tech\DpoPayments\Console\Commands\DpoStatusCommand;
use Mak8Tech\DpoPayments\Http\Middleware\VerifyDpoCallback;

class DpoPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dpo.php',
            'dpo'
        );

        // Register services as singletons
        $this->app->singleton(DpoService::class, function ($app) {
            return new DpoService(
                config('dpo.company_token'),
                config('dpo.service_type'),
                config('dpo.test_mode', false)
            );
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService($app->make(DpoService::class));
        });

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService($app->make(DpoService::class));
        });

        $this->app->singleton(CountryService::class, function ($app) {
            return new CountryService();
        });

        // Register facade
        $this->app->bind('dpo-payment', function ($app) {
            return $app->make(PaymentService::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dpo');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'dpo');

        // Register middleware
        $this->app['router']->aliasMiddleware('dpo.callback', VerifyDpoCallback::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DpoStatusCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/dpo.php' => config_path('dpo.php'),
            ], 'dpo-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'dpo-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/dpo'),
            ], 'dpo-views');

            // Publish React components
            $this->publishes([
                __DIR__ . '/../resources/js/components' => resource_path('js/vendor/dpo'),
            ], 'dpo-react');

            // Publish assets
            // $this->publishes([
            //     __DIR__ . '/../resources/css' => public_path('vendor/dpo/css'),
            //     __DIR__ . '/../dist' => public_path('vendor/dpo/js'),
            // ], 'dpo-assets');

            // Publish language files
            // $this->publishes([
            //     __DIR__ . '/../resources/lang' => resource_path('lang/vendor/dpo'),
            // ], 'dpo-lang');
        }

        // Register view components
        $this->loadViewComponentsAs('dpo', [
            \Mak8Tech\DpoPayments\View\Components\PaymentForm::class,
            \Mak8Tech\DpoPayments\View\Components\TransactionTable::class,
            \Mak8Tech\DpoPayments\View\Components\SubscriptionManager::class,
        ]);
    }
}
