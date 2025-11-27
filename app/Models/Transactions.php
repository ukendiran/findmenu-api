<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'transactions';
    protected $dates = ['deleted_at', 'created_at'];

    protected $fillable = [
        'businessId',
        'paymentId',
        'payment_id',
        'userId',
        'type',
        'amount',
        'currency',
        'description',
        'gatewayTransactionId',
        'metadata',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
