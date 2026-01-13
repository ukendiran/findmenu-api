<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Subscription extends Model
{
    use SoftDeletes;
    
    protected $table = 'subscriptions';

    protected $fillable = [
        'businessId',
        'planId',
        'paymentId',
        'payment_gateway',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'auto_renew',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'auto_renew' => 'boolean',
        'status' => 'integer',
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

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'subscriptionId');
    }

    public function isActive()
    {
        return $this->status === 1 && Carbon::now()->lte($this->ends_at);
    }

    public function isTrial()
    {
        return $this->status === 4 && $this->trial_ends_at && Carbon::now()->lte($this->trial_ends_at);
    }

    public function isExpired()
    {
        return Carbon::now()->gt($this->ends_at);
    }
}
