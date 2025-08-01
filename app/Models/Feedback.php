<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'feedbacks';
    protected $dates = ['deleted_at', 'created_at'];

    protected $fillable = [
        'message',
        'status',
        'createdAt',
        'businessId'
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
}
