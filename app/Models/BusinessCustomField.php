<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BusinessCustomField extends Model
{
    protected $fillable = ['business_id', 'custom_field_id', 'value'];

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}