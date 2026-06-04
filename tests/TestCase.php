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
}
