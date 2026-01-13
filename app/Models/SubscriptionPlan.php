<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;
    
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_period',
        'features',
        'trial_days',
        'payment_gateways',
        'status',
        'is_renewable',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'payment_gateways' => 'array',
        'trial_days' => 'integer',
        'status' => 'integer',
        'is_renewable' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'planId');
    }
}
