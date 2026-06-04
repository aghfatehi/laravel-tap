<?php

namespace Aghfatehi\Tap\Tests\Feature;

use Aghfatehi\Tap\Tests\TestCase;

class TapChargeControllerTest extends TestCase
{

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
