<?php

namespace Aghfatehi\Tap\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentSucceeded
{
    use Dispatchable;

    public function __construct(
        public array $payload,
        public string $tapId,
        public string $status = 'CAPTURED'
    ) {}
}
