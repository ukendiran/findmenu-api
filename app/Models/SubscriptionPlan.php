<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    public $timestamps = false;
    protected $table = 'subscription_plans';
    protected $dates = ['created_at'];

    protected $fillable = [
        'name',
        'slug',
        'price',
        'payment_gateway',
        'billing_period',
        'features',
        'status',
        'created_at',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];    
}
