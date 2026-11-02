<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
