<?php

namespace Aghfatehi\Tap\Controllers;

use Aghfatehi\Tap\Facades\Tap;
use Aghfatehi\Tap\Models\TapTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ChargeController extends Controller
{
    public function pay(Request $request)
    {
        Log::info('Initiating Tap charge...');

        $amount = $request->input('amount', 0);
        $currency = config('tap.currency', 'SAR');

        $user = $request->user();
        $firstName = $user?->name ?? $request->input('first_name', 'Customer');
        $lastName = $user?->name ?? $request->input('last_name', 'Customer');
        $email = $user?->email ?? $request->input('email', 'customer@example.com');
        $phone = $request->input('phone', '');

        $requestBody = [
            'amount' => (float) $amount,
            'currency' => $currency,
            'customer_initiated' => true,
            'threeDSecure' => true,
            'description' => $request->input('description', 'Order payment'),
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => ['country_code' => '966', 'number' => $phone],
            ],
            'merchant' => [
                'id' => config('tap.merchant_id'),
            ],
            'source' => ['id' => 'src_all'],
            'redirect' => [
                'url' => route('tap.callback'),
            ],
            'post' => [
                'url' => route('tap.webhook'),
            ],
            'reference' => [
                'order' => $request->input('order_id', 'ORD-' . time()),
            ],
            'metadata' => [
                'udf1' => $request->input('udf1', ''),
                'udf2' => $request->input('udf2', ''),
                'udf3' => $request->input('udf3', ''),
            ],
        ];

        try {
            $response = Tap::createCharge($requestBody);
            Log::info('Tap Charge Response', ['id' => $response['id'] ?? null]);

            if (isset($response['id'])) {
                TapTransaction::create([
                    'tap_id' => $response['id'],
                    'tap_object' => 'charge',
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $response['status'] ?? 'INITIATED',
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'request_payload' => $requestBody,
                    'response_payload' => $response,
                ]);

                if (isset($response['transaction']['url'])) {
                    session(['tap_charge_id' => $response['id']]);
                    return redirect()->away($response['transaction']['url']);
                }

                return response()->json($response);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to initiate payment']);
        } catch (\Throwable $e) {
            Log::error('Tap Charge Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function callback(Request $request)
    {
        Log::info('Tap Callback', $request->all());

        $tapId = $request->input('tap_id')
            ?? $request->input('charge_id')
            ?? session('tap_charge_id');

        if (!$tapId) {
            return redirect()->route('home')->withErrors(['error' => 'Payment verification failed']);
        }

        try {
            $response = Tap::getCharge($tapId);
            Log::info('Tap Charge Status', ['id' => $tapId, 'status' => $response['status'] ?? null]);

            $transaction = TapTransaction::where('tap_id', $tapId)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => $response['status'] ?? $transaction->status,
                    'response_payload' => $response,
                    'customer_id' => $response['customer']['id'] ?? null,
                    'payment_method' => $response['source']['payment_method'] ?? null,
                    'source_id' => $response['source']['id'] ?? null,
                ]);
            }

            $status = $response['status'] ?? '';
            $successStatuses = ['CAPTURED', 'AUTHORIZED'];

            if (in_array($status, $successStatuses, true)) {
                return redirect()->route('home')->with('success', __('Payment completed successfully'));
            }

            return redirect()->route('home')->withErrors(['error' => __('Payment was not completed')]);
        } catch (\Throwable $e) {
            Log::error('Tap Callback Error: ' . $e->getMessage());
            return redirect()->route('home')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request)
    {
        Log::info('Tap Payment Cancelled');
        return redirect()->route('home')->with('warning', __('Payment was cancelled'));
    }

    public function failure(Request $request)
    {
        Log::info('Tap Payment Failed');
        return redirect()->route('home')->withErrors(['error' => __('Payment failed')]);
    }
}
