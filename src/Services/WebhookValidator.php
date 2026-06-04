<?php

namespace Aghfatehi\Tap\Services;

use Illuminate\Support\Facades\Log;

class WebhookValidator
{
    /**
     * Verify the webhook hashstring sent by Tap in the request header.
     *
     * Constructs the to-be-hashed string from the payload fields,
     * then compares HMAC-SHA256(toBeHashed, secretKey) against the
     * hashstring header value.
     *
     * @param  array   $payload          Decoded webhook body
     * @param  string  $receivedHash     The hashstring from the header
     * @param  string  $secretKey        Your Tap secret API key
     * @param  string  $type             'charge' | 'authorize' | 'refund' | 'invoice'
     * @return bool
     */
    public function verify(array $payload, string $receivedHash, string $secretKey, string $type = 'charge'): bool
    {
        $id = $payload['id'] ?? '';
        $amount = $payload['amount'] ?? 0;
        $currency = $payload['currency'] ?? '';
        $gatewayReference = $payload['reference']['gateway'] ?? '';
        $paymentReference = $payload['reference']['payment'] ?? '';
        $status = $payload['status'] ?? '';

        $created = $payload['transaction']['created']
            ?? $payload['created']
            ?? '';

        $amount = $this->formatAmount($amount, $currency);

        $toBeHashed = "x_id{$id}"
            . "x_amount{$amount}"
            . "x_currency{$currency}"
            . "x_gateway_reference{$gatewayReference}"
            . "x_payment_reference{$paymentReference}"
            . "x_status{$status}"
            . "x_created{$created}";

        $computedHash = hash_hmac('sha256', $toBeHashed, $secretKey);

        $isValid = hash_equals($computedHash, $receivedHash);

        if (!$isValid) {
            Log::warning('Tap Webhook hashstring validation failed', [
                'expected' => $computedHash,
                'received' => $receivedHash,
                'id' => $id,
            ]);
        }

        return $isValid;
    }

    /**
     * Round amount to the standard decimal places for the currency.
     *
     * AED, SAR, QAR, USD, EUR, GBP, EGP → 2 decimal places
     * BHD, KWD, OMR, JOD               → 3 decimal places
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $threeDecimalCurrencies = ['BHD', 'KWD', 'OMR', 'JOD'];
        $decimals = in_array(strtoupper($currency), $threeDecimalCurrencies, true) ? 3 : 2;
        return number_format($amount, $decimals, '.', '');
    }
}
