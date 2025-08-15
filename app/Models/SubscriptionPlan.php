<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_period',
        'features',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
        'status' => 'boolean',
    ];

    // Accessor for formatted price
    public function getFormattedPriceAttribute()
    {
        return '₹'.number_format($this->price, 2);
    }

    // Accessor for billing period display
    public function getBillingPeriodLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->billing_period));
    }

    // Scope to get active plans
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
