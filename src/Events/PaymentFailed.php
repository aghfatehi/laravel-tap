<?php

namespace Aghfatehi\Tap\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public array $payload,
        public string $tapId,
        public string $status,
        public ?string $errorMessage = null
    ) {}
}
