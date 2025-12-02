<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function fields()
    {
        return $this->hasMany(BusinessTypeField::class)->orderBy('order');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'business_type_id');
    }
}
