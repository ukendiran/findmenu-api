<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'payments';
    protected $dates = ['deleted_at', 'created_at'];

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
        'deleted_at',
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
