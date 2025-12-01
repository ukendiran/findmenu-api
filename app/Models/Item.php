<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;
    protected $table = 'items';
    protected $dates = ['created_at'];

    protected $fillable = [
        'name',
        'businessId',
        'categoryId',
        'subCategoryId',
        'image',
        'description',
        'status',
        'price',
        'isAvailable',
        'foodType',
        'createdAt',
        'createdBy',
        'menuOrderId'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }

    public function category()
    {
        return $this->belongsTo(MainCategory::class, 'categoryId');
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'subCategoryId');
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
