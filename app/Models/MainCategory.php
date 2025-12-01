<?php

/**
 * @OA\Schema(
 *     title="MainCategory",
 *     description="Main Category model",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="image", type="string"),
 *     @OA\Property(property="isAvailable", type="integer"),
 *     @OA\Property(property="menuOrderId", type="integer"),
 *     @OA\Property(property="businessId", type="integer"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="integer")
 * )
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainCategory extends Model
{
    public $timestamps = false;
    protected $table = 'main_categories';
    protected $dates = ['created_at'];

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'created_at',
        'businessId',
        'isAvailable',
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
    public function subCategory()
    {
        return $this->hasMany(SubCategory::class, 'categoryId');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
