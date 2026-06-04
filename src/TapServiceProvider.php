<?php

namespace Aghfatehi\Tap;

use Aghfatehi\Tap\Events\PaymentFailed;
use Aghfatehi\Tap\Events\PaymentSucceeded;
use Aghfatehi\Tap\Events\WebhookReceived;
use Aghfatehi\Tap\Listeners\LogPaymentEvent;
use Aghfatehi\Tap\Services\TapClient;
use Aghfatehi\Tap\Services\WebhookValidator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/tap.php' => config_path('tap.php'),
        ], 'tap-config');

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'tap-migrations');
        }

        Event::listen(PaymentSucceeded::class, LogPaymentEvent::class);
        Event::listen(PaymentFailed::class, LogPaymentEvent::class);
        Event::listen(WebhookReceived::class, LogPaymentEvent::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/tap.php', 'tap');

        $this->app->singleton('tap.client', function ($app) {
            return new TapClient();
        });

        $this->app->singleton(WebhookValidator::class, function ($app) {
            return new WebhookValidator();
        });
    }

    public function provides(): array
    {
        return ['tap.client', TapClient::class, WebhookValidator::class];
    }
}
