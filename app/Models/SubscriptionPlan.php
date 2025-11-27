<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'subscription_plans';
    protected $dates = ['deleted_at', 'created_at'];

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
        'deleted_at',
    ];    
}
