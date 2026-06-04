<?php

namespace Aghfatehi\Tap\Controllers;

use Aghfatehi\Tap\Facades\Tap;
use Aghfatehi\Tap\Models\TapTransaction;
use Aghfatehi\Tap\Services\WebhookValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();
        $receivedHash = $request->header('hashstring', '');
        $secretKey = config('tap.secret_key');

        Log::info('Tap Webhook Received', [
            'id' => $payload['id'] ?? null,
            'status' => $payload['status'] ?? null,
            'event' => $payload['event_type'] ?? 'unknown',
        ]);

        $validator = app(WebhookValidator::class);

        $type = $this->detectType($payload);
        $isValid = $validator->verify($payload, $receivedHash, $secretKey, $type);

        if (!$isValid) {
            Log::warning('Tap Webhook rejected — invalid hashstring', [
                'id' => $payload['id'] ?? null,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $this->processWebhook($payload);

        return response()->json(['success' => true]);
    }

    protected function detectType(array $payload): string
    {
        $object = $payload['object'] ?? '';
        if ($object === 'authorize') {
            return 'authorize';
        }
        if (isset($payload['refund'])) {
            return 'refund';
        }
        if ($object === 'invoice') {
            return 'invoice';
        }
        return 'charge';
    }

    protected function processWebhook(array $payload): void
    {
        $tapId = $payload['id'] ?? '';
        $status = $payload['status'] ?? '';

        $transaction = TapTransaction::where('tap_id', $tapId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => $status,
                'response_payload' => $payload,
                'customer_id' => $payload['customer']['id'] ?? $transaction->customer_id,
                'payment_method' => $payload['source']['payment_method'] ?? $transaction->payment_method,
                'source_id' => $payload['source']['id'] ?? $transaction->source_id,
            ]);
            Log::info('Tap Transaction updated via webhook', ['id' => $tapId, 'status' => $status]);
        } else {
            TapTransaction::create([
                'tap_id' => $tapId,
                'tap_object' => $payload['object'] ?? 'unknown',
                'amount' => $payload['amount'] ?? 0,
                'currency' => $payload['currency'] ?? 'SAR',
                'status' => $status,
                'customer_email' => $payload['customer']['email'] ?? null,
                'response_payload' => $payload,
            ]);
            Log::info('Tap Transaction created via webhook', ['id' => $tapId, 'status' => $status]);
        }
    }
}
