# Laravel Tap Payment Gateway

[![Latest Version](https://img.shields.io/packagist/v/aghfatehi/laravel-tap.svg)](https://packagist.org/packages/aghfatehi/laravel-tap)
[![Laravel](https://img.shields.io/badge/Laravel-10~13-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/github/license/aghfatehi/laravel-tap)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/aghfatehi/laravel-tap.svg)](https://packagist.org/packages/aghfatehi/laravel-tap)

A professional Laravel package for integrating [Tap Payments](https://tap.company) - the unified payment platform in the Middle East. Supports KNET, Mada, Visa, Mastercard, American Express, Apple Pay, Google Pay, STC Pay, Tabby, Tamara, Benefit, Fawry, OmanNet, NAPS, and more.

---

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Architecture Overview](#architecture-overview)
- [Installation](#installation)
- [Configuration](#configuration)
- [Phase 1: Payment Processing (Charges)](#-phase-1-payment-processing-charges)
- [Phase 2: Order Management](#-phase-2-order-management)
- [API Methods](#api-methods)
- [Webhook Handling](#webhook-handling)
- [Testing](#testing)
- [Security](#security)
- [Integration Scenarios](#integration-scenarios)
- [Countries & Currencies](#countries--currencies)

---

## Features

### Phase 1 — Payment Processing
- ✅ Create Charge (hosted checkout page — `src_all`)
- ✅ Retrieve Charge (verify payment status)
- ✅ List Charges (with filters)
- ✅ Update Charge (metadata/description)
- ✅ Webhook receiver with hashstring validation
- ✅ Transaction logging (migration included)
- ✅ Sandbox & Production environments
- ✅ Multiple payment sources (cards, KNET, Mada, STC Pay, Apple Pay, etc.)

### Phase 2 — Order Management
- ✅ Authorize (hold payment, auth-only)
- ✅ Capture (collect authorized payment)
- ✅ Void (cancel authorized payment)
- ✅ Refund (return funds)
- ✅ Token Management (create tokens)
- ✅ Customer Management (create/list)
- ✅ Saved Cards (list/delete)

---

## Requirements

| Laravel | PHP   | Package Version |
|---------|-------|-----------------|
| 10.x    | ^8.1  | ^1.0            |
| 11.x    | ^8.2  | ^1.0            |
| 12.x    | ^8.2  | ^1.0            |
| 13.x    | ^8.2  | ^1.0            |

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                       YOUR LARAVEL APP                          │
│                                                                 │
│  ┌──────────────┐   ┌───────────────┐   ┌───────────────────┐  │
│  │ ChargeController │  │ OrderController  │  │ WebhookController │  │
│  └───────┬───────┘   └───────┬───────┘   └────────┬──────────┘  │
│          │                   │                     │             │
│  ┌───────▼───────────────────▼─────────────────────▼──────────┐  │
│  │                   TapClient (cURL)                         │  │
│  │    createCharge | getCharge | captureCharge | refund...    │  │
│  └───────────────────────┬────────────────────────────────────┘  │
│                          │                                       │
│                    ┌─────▼──────┐                                │
│                    │ Tap API v2 │                                │
│                    │ api.tap.co │                                │
│                    └────────────┘                                │
└─────────────────────────────────────────────────────────────────┘
```

**Core Components:**

| Component | File | Responsibility |
|-----------|------|---------------|
| `TapClient` | `src/Services/TapClient.php` | All cURL calls to Tap API v2 |
| `WebhookValidator` | `src/Services/WebhookValidator.php` | HMAC-SHA256 hashstring verification |
| `Tap Facade` | `src/Facades/Tap.php` | `Tap::createCharge(...)` fluent API |
| `ChargeController` | `src/Controllers/ChargeController.php` | Phase 1 route handlers |
| `OrderController` | `src/Controllers/OrderController.php` | Phase 2 route handlers |
| `WebhookController` | `src/Controllers/WebhookController.php` | Webhook receiver |
| `TapTransaction` | `src/Models/TapTransaction.php` | DB logging model |
| Events | `src/Events/*.php` | PaymentSucceeded, PaymentFailed, WebhookReceived |

---

## Installation

```bash
composer require aghfatehi/laravel-tap
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=tap-config
```

### Publish & Run Migration (Optional but Recommended)

```bash
php artisan vendor:publish --tag=tap-migrations
php artisan migrate
```

---

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
TAP_MERCHANT_ID=your_merchant_id_here
TAP_SECRET_KEY=sk_test_your_secret_key_here
TAP_PUBLIC_KEY=pk_test_your_public_key_here
TAP_SANDBOX_MODE=true
TAP_CURRENCY=SAR
TAP_LOCALE=en
TAP_ROUTE_PREFIX=tap
TAP_REDIRECT_URL=/tap/callback
TAP_WEBHOOK_URL=/tap/webhook
```

| Variable | Description |
|----------|-------------|
| `TAP_MERCHANT_ID` | Your merchant ID from Tap Dashboard (Accounts → Operators → Merchant) |
| `TAP_SECRET_KEY` | Secret API key (`sk_test_xxx` for sandbox, `sk_live_xxx` for production) |
| `TAP_PUBLIC_KEY` | Public API key (`pk_test_xxx` for sandbox, `pk_live_xxx` for production) |
| `TAP_SANDBOX_MODE` | `true` for sandbox (testing), `false` for production |
| `TAP_CURRENCY` | 3-letter ISO currency code (SAR, AED, KWD, USD, etc.) |
| `TAP_LOCALE` | Checkout page language: `en` or `ar` |
| `TAP_ROUTE_PREFIX` | URL prefix for all Tap routes (default: `tap`) |

### Service Provider & Facade

The package auto-discovers via Laravel's package discovery. If disabled, register manually:

```php
// config/app.php
'providers' => [
    Aghfatehi\Tap\TapServiceProvider::class,
],

'aliases' => [
    'Tap' => Aghfatehi\Tap\Facades\Tap::class,
],
```

---

## 🟢 Phase 1: Payment Processing (Charges)

Phase 1 covers the basic "Charge" flow — the most common integration. Customer is redirected to a Tap-hosted checkout page, pays, and returns to your app.

### User Journey (Step by Step)

```
1. Customer clicks "Pay with Tap" on your site
2. Your backend calls Tap API → POST /v2/charges/
3. Tap returns {id: "chg_xxx", transaction.url: "https://..."}
4. You redirect customer to transaction.url (Tap hosted page)
5. Customer selects payment method (KNET, Mada, Card, Apple Pay, etc.)
6. Customer completes 3DS authentication if required
7. Tap redirects customer back to your {redirect.url}
8. Your backend verifies: GET /v2/charges/{id}
9. Tap sends webhook to your /tap/webhook (parallel)
10. You validate the webhook hashstring
11. Transaction is logged in tap_transactions table
```

### Routes Registered (Phase 1)

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| POST | `/tap/pay` | `tap.pay` | Initiate charge (hosted checkout) |
| ANY | `/tap/callback` | `tap.callback` | Payment callback/redirect |
| GET | `/tap/cancel` | `tap.cancel` | Cancel handler |
| GET | `/tap/failure` | `tap.failure` | Failure handler |
| POST | `/tap/webhook` | `tap.webhook` | Webhook receiver |

### Quick Start — Phase 1 Only

#### Using the Provided Routes

The simplest integration. Route your users to the checkout form:

```php
// In your view (Blade)
<form action="{{ route('tap.pay') }}" method="POST">
    @csrf
    <input type="hidden" name="amount" value="500.00">
    <input type="hidden" name="description" value="Order #1234">
    <input type="hidden" name="order_id" value="ORD-1234">
    <input type="hidden" name="first_name" value="{{ auth()->user()->name ?? 'Customer' }}">
    <button type="submit">Pay with Tap</button>
</form>
```

The customer will be redirected to the Tap hosted checkout page, then back to `/tap/callback` after payment.

#### Using the Facade Directly (Custom UI)

```php
<?php

namespace App\Http\Controllers;

use Aghfatehi\Tap\Facades\Tap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function charge(Request $request)
    {
        $amount = 500.00;
        $currency = config('tap.currency', 'SAR');

        // ─── Build the charge payload ──────────────────
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'customer_initiated' => true,
            'threeDSecure' => true,
            'description' => 'Order #ORD-1234',
            'customer' => [
                'first_name' => $request->user()?->name ?? 'Ahmed',
                'last_name' => 'Ali',
                'email' => $request->user()?->email ?? 'customer@example.com',
                'phone' => [
                    'country_code' => '966',
                    'number' => '500000001',
                ],
            ],
            'merchant' => [
                'id' => config('tap.merchant_id'),
            ],
            'source' => [
                'id' => 'src_all',  // Show all available payment methods
            ],
            'redirect' => [
                'url' => route('tap.callback'),
            ],
            'post' => [
                'url' => route('tap.webhook'),
            ],
            'reference' => [
                'order' => 'ORD-1234',
            ],
            'metadata' => [
                'udf1' => 'custom_data_1',
                'udf2' => 'custom_data_2',
            ],
        ];

        try {
            $response = Tap::createCharge($payload);

            if (isset($response['id'])) {
                $checkoutUrl = $response['transaction']['url'] ?? null;

                if ($checkoutUrl) {
                    // Save transaction ID for verification
                    session(['tap_charge_id' => $response['id']]);

                    // Redirect to Tap hosted checkout page
                    return redirect()->away($checkoutUrl);
                }

                // Some payment methods are synchronous (e.g., saved cards)
                // — they return status directly
                return response()->json($response);
            }

            return back()->withErrors(['error' => 'Failed to initiate payment']);
        } catch (\Aghfatehi\Tap\Exceptions\TapException $e) {
            Log::error('Tap Charge Error: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function callback(Request $request)
    {
        $tapId = $request->input('tap_id') ?? session('tap_charge_id');

        if (!$tapId) {
            return redirect('/')->withErrors(['error' => 'Payment verification failed']);
        }

        try {
            $response = Tap::getCharge($tapId);
            $status = $response['status'] ?? '';

            if (in_array($status, ['CAPTURED', 'AUTHORIZED'], true)) {
                return redirect('/')->with('success', 'Payment completed successfully');
            }

            return redirect('/')->withErrors(['error' => 'Payment was not completed']);
        } catch (\Throwable $e) {
            return redirect('/')->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

#### Handling Callback (tap/callback)

When the customer completes payment on Tap's page, they are redirected back to `{redirect.url}` with `?tap_id=chg_xxxxxxxx`. Your callback handler should:

1. Get the `tap_id` from query string
2. Call `Tap::getCharge(tap_id)` to verify the status
3. Check if status is `CAPTURED` (success) or any other status
4. Update your order accordingly

---

## 🔵 Phase 2: Order Management

Phase 2 adds Authorize-Capture-Void-Refund flow plus token/customer management.

Additional routes:

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| POST | `/tap/authorize` | `tap.authorize` | Create authorization |
| POST | `/tap/charge/capture` | `tap.capture` | Capture authorized charge |
| POST | `/tap/charge/void` | `tap.void` | Void authorized charge |
| POST | `/tap/charge/refund` | `tap.refund` | Refund a charge |
| GET | `/tap/charge/{id}` | `tap.details` | Get charge details |

### Authorize → Capture Flow

```php
// Step 1: Authorize (hold payment)
$authorize = Tap::createAuthorize([
    'amount' => 500.00,
    'currency' => 'SAR',
    'customer_initiated' => true,
    'customer' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => 'customer@example.com',
        'phone' => ['country_code' => '966', 'number' => '500000001'],
    ],
    'merchant' => ['id' => config('tap.merchant_id')],
    'source' => ['id' => 'src_all'],
    'redirect' => ['url' => route('tap.callback')],
    'post' => ['url' => route('tap.webhook')],
]);

$authorizeId = $authorize['id']; // auth_xxxxx

// After customer completes 3DS on hosted page...

// Step 2: Capture (collect authorized amount)
$captured = Tap::captureCharge($authorizeId, [
    'amount' => 500.00, // optional: can be partial amount
]);

if ($captured['status'] === 'CAPTURED') {
    // Payment collected successfully
}
```

### Void Flow

```php
// Void an authorized (not yet captured) charge
$voided = Tap::voidCharge('auth_xxxxx');
// $voided['status'] === 'VOID'
```

### Refund Flow

```php
// Refund a captured charge (full or partial)
$refunded = Tap::refundCharge('chg_xxxxx', [
    'amount' => 100.00,
    'reason' => 'Customer requested refund',
]);
```

---

## API Methods

### Phase 1: Charges

```php
use Aghfatehi\Tap\Facades\Tap;

// Create a charge (hosted checkout)
$charge = Tap::createCharge([...]);

// Retrieve a charge by ID
$charge = Tap::getCharge('chg_xxxxx');

// List charges with filters
$charges = Tap::listCharges([
    'status' => 'CAPTURED',
    'limit' => 20,
    'period' => ['date_from' => '2024-01-01', 'date_to' => '2024-12-31'],
]);

// Update charge metadata
$updated = Tap::updateCharge('chg_xxxxx', [
    'description' => 'Updated description',
    'metadata' => ['udf1' => 'new_value'],
]);
```

### Phase 2: Authorize & Order Management

```php
// Create authorization
$authorize = Tap::createAuthorize([...]);

// Get authorization details
$auth = Tap::getAuthorize('auth_xxxxx');

// Capture authorized charge
$captured = Tap::captureCharge('auth_xxxxx', ['amount' => 500.00]);

// Void authorized charge
$voided = Tap::voidCharge('auth_xxxxx');

// Refund captured charge
$refunded = Tap::refundCharge('chg_xxxxx', [
    'amount' => 100.00,
    'reason' => 'Customer request',
]);
```

### Phase 2: Tokens, Customers & Saved Cards

```php
// Create a token (from card data or saved card)
$token = Tap::createToken([
    'card' => [
        'number' => 'tok_xxxxx', // from frontend SDK
    ],
]);

// Create a customer
$customer = Tap::createCustomer([
    'first_name' => 'Ahmed',
    'last_name' => 'Ali',
    'email' => 'customer@example.com',
    'phone' => ['country_code' => '966', 'number' => '500000001'],
]);

// List customers
$customers = Tap::listCustomers(['limit' => 10]);

// List cards for a customer
$cards = Tap::listCards('cus_xxxxx');

// Delete a saved card
Tap::deleteCard('card_xxxxx');
```

---

## Webhook Handling

Tap sends server-to-server POST notifications to your webhook URL after payment events.

### Webhook Payload Example

```json
{
    "id": "chg_TS05A4120230736x9K22710693",
    "object": "charge",
    "status": "CAPTURED",
    "amount": 1.0,
    "currency": "SAR",
    "customer": {
        "id": "cus_xxxxx",
        "first_name": "Ahmed",
        "email": "customer@example.com"
    },
    "reference": {
        "gateway": "mada_pg_xxxxx",
        "payment": "4327230736106619650"
    },
    "transaction": {
        "created": "1698392202943"
    }
}
```

### Webhook Hashstring Validation

The package automatically validates the webhook signature. Here's how it works:

```php
// This runs inside WebhookController automatically:
$validator = app(\Aghfatehi\Tap\Services\WebhookValidator::class);

$isValid = $validator->verify(
    payload: $request->all(),
    receivedHash: $request->header('hashstring', ''),
    secretKey: config('tap.secret_key'),
    type: 'charge'
);
```

The hashstring is computed as:

```
HMAC-SHA256(
  "x_id{id}x_amount{amount}x_currency{currency}"
  "x_gateway_reference{gateway_ref}x_payment_reference{payment_ref}"
  "x_status{status}x_created{created}",
  secret_key
)
```

### Custom Webhook Handler

You can listen to the dispatched events:

```php
// In AppServiceProvider::boot()
\Illuminate\Support\Facades\Event::listen(
    \Aghfatehi\Tap\Events\PaymentSucceeded::class,
    function ($event) {
        // Update your order status
        \App\Models\Order::where('reference_id', $event->payload['reference']['order'] ?? '')
            ->update(['status' => 'paid']);
    }
);

\Illuminate\Support\Facades\Event::listen(
    \Aghfatehi\Tap\Events\PaymentFailed::class,
    function ($event) {
        \Log::error('Payment failed', [
            'tap_id' => $event->tapId,
            'error' => $event->errorMessage,
        ]);
    }
);
```

---

## Integration Scenarios

### Scenario A: Phase 1 Only (Minimum Integration)

You want a simple hosted checkout. No authorize/capture/refund.

```bash
composer require aghfatehi/laravel-tap
php artisan vendor:publish --tag=tap-config
```

1. Add your `.env` variables
2. Add a `POST` form to `route('tap.pay')` with `amount`
3. Handle callback at `route('tap.callback')`
4. Done.

### Scenario B: Phase 1 (your own) + Phase 2 (this package)

You already have a charge integration (either via this package, another package, or direct API calls). You want to add authorize/capture/void/refund:

```bash
composer require aghfatehi/laravel-tap
php artisan vendor:publish --tag=tap-config
```

1. Use `Tap::createAuthorize()` for auth-only payments
2. Use `Tap::captureCharge()` to capture
3. Use `Tap::voidCharge()` to void
4. Use `Tap::refundCharge()` to refund
5. Webhook automatically logs all events

### Scenario C: Full Integration (Phase 1 + Phase 2)

All features enabled. This is the recommended approach for production.

```bash
composer require aghfatehi/laravel-tap
php artisan vendor:publish --tag=tap-config
php artisan vendor:publish --tag=tap-migrations
php artisan migrate
```

Set up your webhook URL in your Tap Dashboard:
```
https://yourdomain.com/tap/webhook
```

---

## Routes Reference

| Method | URI | Name | Phase | Description |
|--------|-----|------|-------|-------------|
| POST | `/tap/pay` | `tap.pay` | 1 | Initiate charge (hosted checkout) |
| ANY | `/tap/callback` | `tap.callback` | 1 | Payment callback/redirect |
| GET | `/tap/cancel` | `tap.cancel` | 1 | Cancel handler |
| GET | `/tap/failure` | `tap.failure` | 1 | Failure handler |
| POST | `/tap/webhook` | `tap.webhook` | 1+2 | Webhook receiver |
| POST | `/tap/authorize` | `tap.authorize` | 2 | Create authorization |
| POST | `/tap/charge/capture` | `tap.capture` | 2 | Capture authorized charge |
| POST | `/tap/charge/void` | `tap.void` | 2 | Void authorized charge |
| POST | `/tap/charge/refund` | `tap.refund` | 2 | Refund a charge |
| GET | `/tap/charge/{id}` | `tap.details` | 2 | Get charge details |

### Customising Routes

Publish the config and modify the `routes` section:

```php
// config/tap.php
'routes' => [
    'prefix' => 'payment/tap',  // Custom prefix
    'middleware' => ['web', 'auth'],  // Custom middleware
],
```

---

## Webhook Events

The package dispatches these events:

| Event | When | Payload |
|-------|------|---------|
| `PaymentSucceeded` | Webhook received with CAPTURED/AUTHORIZED status | Full webhook payload |
| `PaymentFailed` | Webhook received with failed/declined status | Full webhook payload |
| `WebhookReceived` | Any webhook received | Payload + validation boolean |

---

## Payment Sources

The `source.id` field determines which payment methods appear:

| Source ID | Payment Methods |
|-----------|----------------|
| `src_all` | All available methods |
| `src_cards` | Card only (Visa, Mastercard, Mada) |
| `src_kw.knet` | KNET only |
| `src_sa.stcpay` | STC Pay only |
| `src_bh.benefit` | Benefit only |
| `src_om.omannet` | OmanNet only |
| `src_qa.naps` | NAPS/QPay only |

For a tokenized card, pass the token ID as source:
```php
'source' => ['id' => 'tok_xxxxx']
```

---

## Countries & Currencies

| Country | Code | Currency | Code | Decimals |
|---------|------|----------|------|----------|
| Saudi Arabia | SA | Saudi Riyal | SAR | 2 |
| UAE | AE | UAE Dirham | AED | 2 |
| Kuwait | KW | Kuwaiti Dinar | KWD | 3 |
| Bahrain | BH | Bahraini Dinar | BHD | 3 |
| Qatar | QA | Qatari Riyal | QAR | 2 |
| Oman | OM | Omani Riyal | OMR | 3 |
| Jordan | JO | Jordanian Dinar | JOD | 3 |
| Egypt | EG | Egyptian Pound | EGP | 2 |
| USA | US | US Dollar | USD | 2 |
| Europe | EU | Euro | EUR | 2 |
| UK | GB | British Pound | GBP | 2 |

---

## Security

### Credential Safety

- **NEVER** hardcode `TAP_SECRET_KEY` in your code — use `.env` only
- Secret keys start with `sk_` — keep them server-side only
- Public keys start with `pk_` — safe to use in frontend
- The package never logs full secret keys (masked as `sk_****`)

### Webhook Validation

All webhooks are validated using HMAC-SHA256 hashstring verification. If the hash doesn't match, the webhook is rejected with HTTP 403.

### Safe Logging

- ✅ API requests logged without sensitive data
- ✅ Transaction IDs logged for debugging
- ✅ Error responses logged with message only
- ❌ Secret keys never logged
- ❌ Card numbers never logged

---

## Testing

### Run Package Tests

```bash
composer test
```

The test suite covers:

- **TapClientTest**: URL resolution, method existence
- **WebhookValidatorTest**: Hashstring generation, validation, currency rounding
- **ChargeControllerTest**: Route accessibility

### Sandbox Credentials

Set these in your `.env` for sandbox testing:

```env
TAP_MERCHANT_ID=599424
TAP_SECRET_KEY=sk_test_YOUR_TAP_SECRET_KEY
TAP_PUBLIC_KEY=pk_test_YOUR_TAP_PUBLIC_KEY
TAP_SANDBOX_MODE=true
```

> **Note**: You can also generate your own test keys from [Tap Dashboard](https://dashboard.tap.company) → Developers → API Keys.

---

### Test Cards — Local Payment Methods

| Method | Card Number | Expiry | PIN | Result |
|--------|------------|--------|-----|--------|
| KNET | `8888880000000001` | 09/30 | `1234` | CAPTURED |
| KNET | `8888880000000002` | 09/30 | `1234` | CAPTURED |
| KNET | `8888880000000001` | 05/21 | `1234` | NOT CAPTURED |
| Benefit | `4600410123456789` | 12/27 | `1234` | CAPTURED |
| Benefit | `7777770123456789` | 12/27 | `1234` | NOT CAPTURED |
| Benefit | `1111110123456789` | 12/27 | `1234` | DECLINED |
| Naps/QPay | `4215375500883243` | 12/25 | `944` (OTP: 1234) | CAPTURED |

> **KNET Page**: Select **KNET Test Card [KNET1]** from the Bank drop-down.

---

### Test Cards — Credit / Debit Cards

| Method | Card Number | 3D Secure |
|--------|------------|-----------|
| MasterCard | `5123450000000008` | Yes |
| MasterCard | `5111111111111118` | No |
| VISA | `4508750015741019` | Yes |
| VISA | `4012000033330026` | No |
| VISA | `4440000009900010` | Yes |
| VISA | `4440000042200014` | Yes |
| VISA | `4440000042200022` | Yes |
| American Express | `345678901234564` | Yes |
| American Express | `371449635398431` | No |
| Mada | `4464040000000007` | Yes |
| Mada | `5588480000000003` | No |
| OmanNet | `4837915060379278` | Yes |

---

### Expiry Date — Response Mapping

Use these expiry dates to simulate specific outcomes:

| Expiry Date | Transaction Response |
|-------------|---------------------|
| 01/39 | APPROVED |
| 05/22 | DECLINED |
| 04/27 | EXPIRED_CARD |
| 08/28 | TIMED_OUT |
| 01/37 | ACQUIRER_SYSTEM_ERROR |
| 02/37 | UNSPECIFIED_FAILURE |
| 05/37 | UNKNOWN |
| 07/30 | APPROVED (OmanNet) |

---

### CSC / CVV — Response Mapping

| CVV | Gateway Code | Meaning |
|-----|-------------|---------|
| `100` | MATCH | CVV matches (MasterCard / Visa) |
| `101` | NOT_PROCESSED | CVV not processed (MasterCard / Visa) |
| `102` | NO_MATCH | CVV does not match (MasterCard / Visa) |
| `1000` | MATCH | CVV matches (American Express) |
| `1010` | NOT_PROCESSED | CVV not processed (American Express) |
| `1020` | NO_MATCH | CVV does not match (American Express) |
| `844` | MATCH | CVV matches (OmanNet, OTP: 9999) |

---

### STC Pay — Test Phone Numbers

| Country Code | Phone Number |
|-------------|-------------|
| 966 | `548220713` |
| 966 | `550955806` |
| 966 | `554748162` |
| 966 | `554774102` |

---

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

---

## Security

If you discover security issues, please email fathi.a.n2002@gmail.com instead of using the issue tracker.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## Support

- **Issues**: [GitHub Issues](https://github.com/aghfatehi/laravel-tap/issues)
- **Tap API Docs**: [https://developers.tap.company](https://developers.tap.company)
- **Author**: AL-AGHBARI Fatehi ([fathi.a.n2002@gmail.com](mailto:fathi.a.n2002@gmail.com))
