<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
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
        'business_type_id',
        'custom_fields',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];

    protected $casts = [
        'custom_fields' => 'array',
    ];

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

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function customFieldsWithValues()
    {
        return $this->hasMany(BusinessCustomField::class);
    }
}
