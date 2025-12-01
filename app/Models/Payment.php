<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;
    protected $table = 'payments';
    protected $dates = ['created_at'];

    protected $fillable = [
        'businessId',
        'planId',
        'userId',
        'amount',
        'currency',
        'gateway',
        'gatewayPaymentId',
        'status',
        'created_at',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'businessId');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'businessId');
    }
}
