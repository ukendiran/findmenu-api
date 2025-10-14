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
        'billing_period',
        'features',
        'status',
        'is_renewable',
        'created_at',
    ];

    protected $casts = [
        'price' => 'float',
        'billing_period' => 'integer',
        'status' => 'integer',
        'is_renewable' => 'integer',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
