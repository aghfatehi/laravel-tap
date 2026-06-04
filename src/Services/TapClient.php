<?php

namespace Aghfatehi\Tap\Services;

use Aghfatehi\Tap\Exceptions\TapException;
use Illuminate\Support\Facades\Log;

class TapClient
{
    protected array $config;

    protected array $defaultHeaders;

    public function __construct()
    {
        $this->config = config('tap');
        $this->defaultHeaders = [
            'Authorization: Bearer ' . $this->config['secret_key'],
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    }

    public function baseUrl(): string
    {
        $urls = $this->config['api_urls'];
        $key = $this->config['sandbox'] ? 'sandbox' : 'production';
        return $urls[$key] ?? 'https://api.tap.company/v2/';
    }

    // ──────────────────────────────────────────────
    //  PHASE 1: CHARGES
    // ──────────────────────────────────────────────

    public function createCharge(array $data): array
    {
        return $this->post('charges', $data);
    }

    public function getCharge(string $chargeId): array
    {
        return $this->get("charges/{$chargeId}");
    }

    public function listCharges(array $filters = []): array
    {
        return $this->post('charges/list', $filters);
    }

    public function updateCharge(string $chargeId, array $data): array
    {
        return $this->put("charges/{$chargeId}", $data);
    }

    // ──────────────────────────────────────────────
    //  PHASE 2: AUTHORIZE
    // ──────────────────────────────────────────────

    public function createAuthorize(array $data): array
    {
        return $this->post('authorize', $data);
    }

    public function getAuthorize(string $authorizeId): array
    {
        return $this->get("authorize/{$authorizeId}");
    }

    // ──────────────────────────────────────────────
    //  PHASE 2: CAPTURE / VOID / REFUND
    // ──────────────────────────────────────────────

    public function captureCharge(string $chargeId, array $data = []): array
    {
        return $this->post("charges/{$chargeId}/capture", $data);
    }

    public function voidCharge(string $chargeId, array $data = []): array
    {
        return $this->post("charges/{$chargeId}/void", $data);
    }

    public function refundCharge(string $chargeId, array $data = []): array
    {
        return $this->post("charges/{$chargeId}/refund", $data);
    }

    // ──────────────────────────────────────────────
    //  PHASE 2: TOKENS & CUSTOMERS & CARDS
    // ──────────────────────────────────────────────

    public function createToken(array $data): array
    {
        return $this->post('tokens', $data);
    }

    public function createCustomer(array $data): array
    {
        return $this->post('customers', $data);
    }

    public function listCustomers(array $filters = []): array
    {
        return $this->post('customers/list', $filters);
    }

    public function listCards(string $customerId): array
    {
        return $this->get("cards/{$customerId}");
    }

    public function deleteCard(string $cardId): array
    {
        return $this->delete("cards/{$cardId}");
    }

    // ──────────────────────────────────────────────
    //  RAW CURL METHODS
    // ──────────────────────────────────────────────

    protected function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl() . ltrim($path, '/');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $this->executeRequest('GET', $url);
    }

    protected function post(string $path, array $body = []): array
    {
        $url = $this->baseUrl() . ltrim($path, '/');
        return $this->executeRequest('POST', $url, $body);
    }

    protected function put(string $path, array $body = []): array
    {
        $url = $this->baseUrl() . ltrim($path, '/');
        return $this->executeRequest('PUT', $url, $body);
    }

    protected function delete(string $path): array
    {
        $url = $this->baseUrl() . ltrim($path, '/');
        return $this->executeRequest('DELETE', $url);
    }

    protected function executeRequest(string $method, string $url, array $body = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (in_array($method, ['POST', 'PUT'], true) && !empty($body)) {
            $encoded = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $response = $rawResponse ? (json_decode($rawResponse, true) ?? []) : [];

        Log::info('Tap API Request', [
            'method' => $method,
            'path' => $this->maskUrl($url),
            'status' => $httpCode,
        ]);

        if ($curlError) {
            Log::error('Tap API cURL Error: ' . $curlError);
            throw new TapException(
                message: 'Tap API connection failed: ' . $curlError,
                code: 0
            );
        }

        if ($httpCode >= 400) {
            $errorMessage = $response['errors'][0]['description']
                ?? $response['errors'][0]['message']
                ?? $response['message']
                ?? 'Tap API returned status ' . $httpCode;

            Log::error('Tap API Error', [
                'path' => $this->maskUrl($url),
                'status' => $httpCode,
                'response' => $response,
            ]);

            throw new TapException(
                message: $errorMessage,
                errors: $response['errors'] ?? [],
                rawResponse: $response,
                code: $httpCode
            );
        }

        return $response;
    }

    protected function maskUrl(string $url): string
    {
        return preg_replace(
            '/Bearer\s+sk_[^\s"]+/i',
            'Bearer sk_****',
            $url
        );
    }
}
