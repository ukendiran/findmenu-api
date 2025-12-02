<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessTypeField extends Model
{
    protected $fillable = [
        'business_type_id',
        'field_name',
        'field_label',
        'field_type',
        'is_required',
        'default_value',
        'order',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
    ];

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
