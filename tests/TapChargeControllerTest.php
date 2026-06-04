<?php

namespace Aghfatehi\Tap\Tests;

use Orchestra\Testbench\TestCase;

class TapChargeControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Aghfatehi\Tap\TapServiceProvider::class,
        ];
    }

    /** @test */
    public function it_can_access_cancel_route()
    {
        $response = $this->get(route('tap.cancel'));
        $response->assertStatus(302);
    }

    /** @test */
    public function it_can_access_failure_route()
    {
        $response = $this->get(route('tap.failure'));
        $response->assertStatus(302);
    }
}
