<?php

namespace Aghfatehi\Tap\Tests\Unit;

use Aghfatehi\Tap\Services\TapClient;
use Aghfatehi\Tap\Tests\TestCase;

class TapClientTest extends TestCase
{
    protected TapClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TapClient();
    }

    /** @test */
    public function it_resolves_sandbox_base_url()
    {
        config(['tap.sandbox' => true]);
        $url = $this->client->baseUrl();
        $this->assertEquals('https://api.tap.company/v2/', $url);
    }

    /** @test */
    public function it_resolves_production_base_url()
    {
        config(['tap.sandbox' => false]);
        $url = $this->client->baseUrl();
        $this->assertEquals('https://api.tap.company/v2/', $url);
    }

    /** @test */
    public function it_has_all_required_methods()
    {
        $methods = [
            'createCharge', 'getCharge', 'listCharges', 'updateCharge',
            'createAuthorize', 'getAuthorize',
            'captureCharge', 'voidCharge', 'refundCharge',
            'createToken', 'createCustomer', 'listCustomers',
            'listCards', 'deleteCard',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->client, $method),
                "Method {$method} does not exist on TapClient"
            );
        }
    }
}
