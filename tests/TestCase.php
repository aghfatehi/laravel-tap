<?php

namespace Aghfatehi\Tap\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \Aghfatehi\Tap\TapServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('tap.sandbox', true);
        $app['config']->set('tap.secret_key', 'sk_test_xxxxxxxxxxxxxxxx');
        $app['config']->set('tap.merchant_id', 'test_merchant');
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);
        $app['config']->set('app.key', 'base64:qu7s7FJ3vELxCrRbSnP7GK7OGtHZHaGALm3TB2H0v0c=');
    }

    protected function defineRoutes($router)
    {
        $router->get('/', function () {
            return 'Home';
        })->name('home');
    }
}
