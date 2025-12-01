<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'token',
        'mobile',
        'phone',
        'gender',
        'status',
        'profileImage',
        'image',
        'businessId',
        'role',
        'theme_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ✅ JWTSubject required methods
    public function getJWTIdentifier()
    {
        return $this->getKey(); // usually the user id
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function config()
    {
        return $this->hasOne(Config::class, 'businessId', 'businessId');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }
}
