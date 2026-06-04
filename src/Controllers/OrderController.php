<?php

namespace Aghfatehi\Tap\Controllers;

use Aghfatehi\Tap\Facades\Tap;
use Aghfatehi\Tap\Models\TapTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // ──────────────────────────────────────────────
    //  AUTHORIZE
    // ──────────────────────────────────────────────

    public function authorize(Request $request)
    {
        $amount = $request->input('amount', 0);
        $currency = config('tap.currency', 'SAR');

        $user = $request->user();

        $requestBody = [
            'amount' => (float) $amount,
            'currency' => $currency,
            'customer_initiated' => true,
            'threeDSecure' => true,
            'description' => $request->input('description', 'Order authorization'),
            'customer' => [
                'first_name' => $user?->name ?? $request->input('first_name', 'Customer'),
                'last_name' => $request->input('last_name', ''),
                'email' => $user?->email ?? $request->input('email', 'customer@example.com'),
                'phone' => ['country_code' => '966', 'number' => $request->input('phone', '')],
            ],
            'merchant' => ['id' => config('tap.merchant_id')],
            'source' => ['id' => 'src_all'],
            'redirect' => ['url' => route('tap.callback')],
            'post' => ['url' => route('tap.webhook')],
            'reference' => ['order' => $request->input('order_id', 'ORD-' . time())],
        ];

        try {
            $response = Tap::createAuthorize($requestBody);
            Log::info('Tap Authorize Response', ['id' => $response['id'] ?? null]);

            if (isset($response['id'])) {
                TapTransaction::create([
                    'tap_id' => $response['id'],
                    'tap_object' => 'authorize',
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $response['status'] ?? 'INITIATED',
                    'request_payload' => $requestBody,
                    'response_payload' => $response,
                ]);

                if (isset($response['transaction']['url'])) {
                    return redirect()->away($response['transaction']['url']);
                }

                return response()->json($response);
            }

            return response()->json(['error' => 'Failed to initiate authorization'], 400);
        } catch (\Throwable $e) {
            Log::error('Tap Authorize Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  CAPTURE
    // ──────────────────────────────────────────────

    public function capture(Request $request)
    {
        $chargeId = $request->input('charge_id');
        $amount = $request->input('amount');

        if (!$chargeId) {
            return response()->json(['error' => 'charge_id is required'], 400);
        }

        $data = [];
        if ($amount !== null) {
            $data['amount'] = (float) $amount;
        }

        try {
            $response = Tap::captureCharge($chargeId, $data);
            Log::info('Tap Capture Response', ['id' => $chargeId, 'status' => $response['status'] ?? null]);

            $this->updateTransaction($chargeId, $response, 'capture');

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Tap Capture Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  VOID
    // ──────────────────────────────────────────────

    public function void(Request $request)
    {
        $chargeId = $request->input('charge_id');

        if (!$chargeId) {
            return response()->json(['error' => 'charge_id is required'], 400);
        }

        try {
            $response = Tap::voidCharge($chargeId);
            Log::info('Tap Void Response', ['id' => $chargeId, 'status' => $response['status'] ?? null]);

            $this->updateTransaction($chargeId, $response, 'void');

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Tap Void Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  REFUND
    // ──────────────────────────────────────────────

    public function refund(Request $request)
    {
        $chargeId = $request->input('charge_id');
        $amount = $request->input('amount');
        $reason = $request->input('reason', '');

        if (!$chargeId) {
            return response()->json(['error' => 'charge_id is required'], 400);
        }

        $data = array_filter([
            'amount' => $amount !== null ? (float) $amount : null,
            'reason' => $reason,
        ]);

        try {
            $response = Tap::refundCharge($chargeId, $data);
            Log::info('Tap Refund Response', ['id' => $chargeId, 'status' => $response['status'] ?? null]);

            $this->updateTransaction($chargeId, $response, 'refund');

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Tap Refund Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  CHARGE DETAILS
    // ──────────────────────────────────────────────

    public function details(string $id)
    {
        try {
            $response = Tap::getCharge($id);
            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────

    protected function updateTransaction(string $tapId, array $response, string $action): void
    {
        $transaction = TapTransaction::where('tap_id', $tapId)->first();
        if ($transaction) {
            $transaction->update([
                'status' => $response['status'] ?? $transaction->status,
                'response_payload' => $response,
            ]);
            Log::info("Tap Transaction {$action}d", ['id' => $tapId, 'status' => $response['status'] ?? null]);
        }
    }
}
