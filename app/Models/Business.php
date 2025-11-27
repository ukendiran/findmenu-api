<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use SoftDeletes;
    protected $table = 'businesses';
    protected $fillable = [
        'name',
        'code',
        'email',
        'mobile',
        'address',
        'logo',
        'image',
        'bannerImage',
        'type',
        'status',
        'group_id',
        'createdAt',
        'social',
        'description',
        'reviewId',
        'stars',
        'reviews',
        'map_url',
        'review_url',
        'currency',
        'is_featured',
        'license_no',
        'businessType',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $timestamps = false;

    protected $dates = ['deleted_at', 'created_at'];


    public function config()
    {
        return $this->hasOne(Config::class, 'businessId'); // or 'businessId' if you renamed it
    }


    public function category()
    {
        return $this->hasMany(MainCategory::class, 'businessId'); // or 'businessId' if you renamed it
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
