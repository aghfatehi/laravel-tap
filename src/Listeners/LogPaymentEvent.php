<?php

namespace Aghfatehi\Tap\Listeners;

use Aghfatehi\Tap\Events\PaymentFailed;
use Aghfatehi\Tap\Events\PaymentSucceeded;
use Aghfatehi\Tap\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;

class LogPaymentEvent
{
    public function handle(PaymentSucceeded|PaymentFailed|WebhookReceived $event): void
    {
        $level = 'info';
        $message = 'Tap payment event';

        if ($event instanceof PaymentSucceeded) {
            $message = 'Tap payment succeeded';
        } elseif ($event instanceof PaymentFailed) {
            $level = 'error';
            $message = 'Tap payment failed: ' . ($event->errorMessage ?? 'unknown');
        } elseif ($event instanceof WebhookReceived) {
            $message = 'Tap webhook received (valid: ' . ($event->valid ? 'yes' : 'no') . ')';
            $level = $event->valid ? 'info' : 'warning';
        }

        Log::$level($message, [
            'tap_id' => $event->tapId ?? $event->payload['id'] ?? null,
            'status' => $event->payload['status'] ?? $event->status ?? null,
        ]);
    }
}
