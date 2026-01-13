<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CustomField extends Model
{
    protected $fillable = ['business_type_id', 'field_name', 'field_label', 'field_type'];
}