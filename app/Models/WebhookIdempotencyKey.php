<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookIdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'order_id',
        'response',
        'processed_at',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
