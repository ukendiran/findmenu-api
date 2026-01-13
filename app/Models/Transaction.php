<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'businessId',
        'subscriptionId',
        'paymentId',
        'transaction_type',
        'amount',
        'currency',
        'gateway',
        'status',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscriptionId');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'paymentId');
    }
}
