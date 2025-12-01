<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    public $timestamps = false;
    protected $table = 'feedbacks';
    protected $dates = ['created_at'];

    protected $fillable = [
        'message',
        'status',
        'created_at',
        'businessId'
    ];
     protected $hidden = [
        'updated_at',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }
}
