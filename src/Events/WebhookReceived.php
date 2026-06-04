<?php

namespace Aghfatehi\Tap\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WebhookReceived
{
    use Dispatchable;

    public function __construct(
        public array $payload,
        public bool $valid
    ) {}
}
