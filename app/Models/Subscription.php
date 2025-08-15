<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'userId',
        'planId',
        'businessId',
        'payment_gateway',
        'paymentId',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'status' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'planId');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }
}
