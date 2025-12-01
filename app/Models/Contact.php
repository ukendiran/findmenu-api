<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    public $timestamps = false;
    protected $dates = ['created_at'];
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'message',
        'status',
        'created_at'
    ];
     protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
