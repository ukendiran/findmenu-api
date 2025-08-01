<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'contacts';
    public $timestamps = false;
    protected $dates = ['deleted_at', 'created_at'];
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
        'deleted_at',
    ];
}
