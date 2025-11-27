<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'subscriptions';
    protected $dates = ['deleted_at', 'created_at'];

    protected $fillable = [
        'businessId',
        'planId',
        'paymentId',
        'payment_gateway',
        'starts_at',
        'ends_at',
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
        return $this->belongsTo(Payment::class, 'paymentId');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'planId');
    }
}
