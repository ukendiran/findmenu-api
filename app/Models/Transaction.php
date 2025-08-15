<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'paymentId',
        'businesId',
        'userId',
        'type',
        'amount',
        'currency',
        'description',
        'status',
        'gateway_transactionId',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'paymentId');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
