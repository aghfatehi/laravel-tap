<?php

namespace Aghfatehi\Tap\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array createCharge(array $data)
 * @method static array getCharge(string $chargeId)
 * @method static array listCharges(array $filters = [])
 * @method static array updateCharge(string $chargeId, array $data)
 * @method static array createAuthorize(array $data)
 * @method static array getAuthorize(string $authorizeId)
 * @method static array captureCharge(string $chargeId, array $data = [])
 * @method static array voidCharge(string $chargeId, array $data = [])
 * @method static array refundCharge(string $chargeId, array $data = [])
 * @method static array createToken(array $data)
 * @method static array createCustomer(array $data)
 * @method static array listCustomers(array $filters = [])
 * @method static array listCards(string $customerId)
 * @method static array deleteCard(string $cardId)
 * @method static \Aghfatehi\Tap\Services\TapClient client()
 *
 * @see \Aghfatehi\Tap\Services\TapClient
 */
class Tap extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tap.client';
    }
}
