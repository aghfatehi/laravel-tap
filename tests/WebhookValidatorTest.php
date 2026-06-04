<?php

namespace Aghfatehi\Tap\Tests;

use Aghfatehi\Tap\Services\WebhookValidator;

class WebhookValidatorTest extends TestCase
{
    protected WebhookValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WebhookValidator();
    }

    /** @test */
    public function it_validates_a_correct_hashstring()
    {
        $secretKey = 'sk_test_xxxxxxxxxxxxxxxx';

        $payload = [
            'id' => 'chg_TS05A4120230736x9K22710693',
            'amount' => 1.0,
            'currency' => 'SAR',
            'status' => 'CAPTURED',
            'reference' => [
                'gateway' => 'mada_pg_xxxxx',
                'payment' => '4327230736106619650',
            ],
            'transaction' => [
                'created' => '1698392202943',
            ],
        ];

        $toBeHashed = "x_id{$payload['id']}"
            . "x_amount1.00"
            . "x_currency{$payload['currency']}"
            . "x_gateway_reference{$payload['reference']['gateway']}"
            . "x_payment_reference{$payload['reference']['payment']}"
            . "x_status{$payload['status']}"
            . "x_created{$payload['transaction']['created']}";

        $correctHash = hash_hmac('sha256', $toBeHashed, $secretKey);

        $result = $this->validator->verify($payload, $correctHash, $secretKey, 'charge');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_an_incorrect_hashstring()
    {
        $secretKey = 'sk_test_xxxxxxxxxxxxxxxx';

        $payload = [
            'id' => 'chg_TS05A4120230736x9K22710693',
            'amount' => 1.0,
            'currency' => 'SAR',
            'status' => 'CAPTURED',
            'reference' => [
                'gateway' => 'mada_pg_xxxxx',
                'payment' => '4327230736106619650',
            ],
            'transaction' => [
                'created' => '1698392202943',
            ],
        ];

        $wrongHash = '0000000000000000000000000000000000000000000000000000000000000000';

        $result = $this->validator->verify($payload, $wrongHash, $secretKey, 'charge');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rounds_amount_correctly_by_currency()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('roundAmount');
        $method->setAccessible(true);

        $this->assertEquals(100.50, $method->invoke($this->validator, 100.5, 'SAR'));
        $this->assertEquals(100.500, $method->invoke($this->validator, 100.5, 'KWD'));
        $this->assertEquals(100.00, $method->invoke($this->validator, 100, 'AED'));
        $this->assertEquals(100.000, $method->invoke($this->validator, 100, 'BHD'));
    }
}
