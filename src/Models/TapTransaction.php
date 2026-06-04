<?php

namespace Aghfatehi\Tap\Models;

use Illuminate\Database\Eloquent\Model;

class TapTransaction extends Model
{
    protected $fillable = [
        'tap_id',
        'tap_object',
        'order_reference_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'customer_id',
        'customer_email',
        'customer_phone',
        'source_id',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'request_payload' => 'json',
        'response_payload' => 'json',
    ];

    public function billable()
    {
        return $this->morphTo();
    }
}
