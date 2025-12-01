<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
    protected $table = 'notifications';
    protected $dates = ['created_at'];

    protected $fillable = [
        'message',
        'status',
        'created_at',
        'businessId'
    ];
     protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }
}
