<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCategory extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'sub_categories';

    protected $dates = ['deleted_at', 'created_at'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'createdAt',
        'businessId',
        'categoryId',
        'isAvailable',
        'menuOrderId',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }

    public function category()
    {
        return $this->belongsTo(MainCategory::class, 'categoryId');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'subCategoryId');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
